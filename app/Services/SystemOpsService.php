<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemOpsService
{
    public function __construct(private TenantManager $tenants) {}

    /**
     * @return array{
     *     separate_databases: bool,
     *     central: array{ran: int, pending: int, last: ?string},
     *     tenant: array{ran: int, pending: int, last: ?string, tenant_count: int}
     * }
     */
    public function migrationStatus(): array
    {
        return [
            'separate_databases' => $this->tenants->separateDatabases(),
            'central' => $this->statusFor(
                connection: $this->tenants->centralConnection(),
                path: database_path('migrations'),
            ),
            'tenant' => array_merge(
                $this->tenantMigrationSnapshot(),
                ['tenant_count' => Tenant::withTrashed()->count()],
            ),
        ];
    }

    /**
     * @return array{exit_code: int, output: string}
     */
    public function runCentralMigrations(): array
    {
        $this->audit('central migrations');
        $this->releaseMigrationLocks();

        $exit = Artisan::call('migrate', [
            '--database' => $this->tenants->centralConnection(),
            '--path' => 'database/migrations',
            '--force' => true,
        ]);

        return [
            'exit_code' => $exit,
            'output' => trim(Artisan::output()) ?: 'Nothing to migrate.',
        ];
    }

    /**
     * @return array{exit_code: int, output: string}
     */
    public function runTenantMigrations(): array
    {
        $this->audit('tenant migrations');
        $this->releaseMigrationLocks();

        $exit = Artisan::call('tenants:migrate');

        return [
            'exit_code' => $exit,
            'output' => trim(Artisan::output()) ?: 'Nothing to migrate.',
        ];
    }

    /**
     * Commit open test/request transactions so migrate DDL is not blocked
     * (SQLite hangs when RefreshDatabase holds a write lock on the same file).
     */
    private function releaseMigrationLocks(): void
    {
        foreach (['sqlite', 'central', 'tenant', 'mysql'] as $name) {
            try {
                DB::connection($name)->unprepared('COMMIT');
            } catch (\Throwable) {
                //
            }
        }
    }

    /**
     * @return list<array{name: string, path: string, size: int, modified_at: ?string}>
     */
    public function listLogFiles(): array
    {
        $dir = storage_path('logs');
        if (! is_dir($dir)) {
            return [];
        }

        $files = collect(File::files($dir))
            ->filter(fn ($file) => str_ends_with(strtolower($file->getFilename()), '.log'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        return $files->map(fn ($file) => [
            'name' => $file->getFilename(),
            'path' => $file->getFilename(),
            'size' => $file->getSize(),
            'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
        ])->all();
    }

    /**
     * @return array{file: string, exists: bool, size: int, lines: list<string>, truncated: bool}
     */
    public function readLogTail(string $file = 'laravel.log', int $lines = 300): array
    {
        $safe = $this->safeLogBasename($file);
        $path = storage_path('logs/'.$safe);
        $lines = max(50, min(1000, $lines));

        if (! is_file($path)) {
            return [
                'file' => $safe,
                'exists' => false,
                'size' => 0,
                'lines' => [],
                'truncated' => false,
            ];
        }

        $size = filesize($path) ?: 0;
        $tail = $this->tailFile($path, $lines);

        return [
            'file' => $safe,
            'exists' => true,
            'size' => $size,
            'lines' => $tail['lines'],
            'truncated' => $tail['truncated'],
        ];
    }

    public function clearLog(string $file = 'laravel.log'): void
    {
        $safe = $this->safeLogBasename($file);
        $path = storage_path('logs/'.$safe);

        $this->audit("clear log {$safe}");

        if (! is_file($path)) {
            File::put($path, '');

            return;
        }

        $handle = fopen($path, 'c');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open log file for clearing.');
        }

        try {
            flock($handle, LOCK_EX);
            ftruncate($handle, 0);
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function downloadLog(string $file = 'laravel.log'): StreamedResponse
    {
        $safe = $this->safeLogBasename($file);
        $path = storage_path('logs/'.$safe);

        if (! is_file($path)) {
            abort(404, 'Log file not found.');
        }

        $this->audit("download log {$safe}");

        return response()->streamDownload(function () use ($path) {
            $handle = fopen($path, 'rb');
            if ($handle === false) {
                return;
            }

            try {
                while (! feof($handle)) {
                    $chunk = fread($handle, 1024 * 64);
                    if ($chunk === false) {
                        break;
                    }
                    echo $chunk;
                }
            } finally {
                fclose($handle);
            }
        }, $safe, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function audit(string $action): void
    {
        $actor = Auth::guard('super')->user();

        Log::info('System ops action', [
            'action' => $action,
            'super_admin_id' => $actor?->id,
            'super_admin_email' => $actor?->email,
        ]);
    }

    /**
     * @return array{ran: int, pending: int, last: ?string}
     */
    private function statusFor(string $connection, string $path): array
    {
        $files = $this->migrationFiles($path);

        try {
            if (! DB::connection($connection)->getSchemaBuilder()->hasTable('migrations')) {
                return [
                    'ran' => 0,
                    'pending' => count($files),
                    'last' => null,
                ];
            }

            $ran = DB::connection($connection)
                ->table('migrations')
                ->orderBy('id')
                ->pluck('migration')
                ->all();

            $ranSet = array_flip($ran);
            $pending = array_values(array_filter($files, fn ($name) => ! isset($ranSet[$name])));

            return [
                'ran' => count($ran),
                'pending' => count($pending),
                'last' => $ran !== [] ? (string) end($ran) : null,
            ];
        } catch (\Throwable) {
            return [
                'ran' => 0,
                'pending' => count($files),
                'last' => null,
            ];
        }
    }

    /**
     * @return array{ran: int, pending: int, last: ?string}
     */
    private function tenantMigrationSnapshot(): array
    {
        $path = database_path('migrations/tenant');
        $files = $this->migrationFiles($path);

        $tenant = Tenant::query()->orderBy('id')->first();
        if (! $tenant) {
            return [
                'ran' => 0,
                'pending' => count($files),
                'last' => null,
            ];
        }

        $previous = $this->tenants->current();

        try {
            $this->tenants->switchTo($tenant);

            return $this->statusFor('tenant', $path);
        } catch (\Throwable) {
            return [
                'ran' => 0,
                'pending' => count($files),
                'last' => null,
            ];
        } finally {
            if ($previous) {
                $this->tenants->switchTo($previous);
            } else {
                app()->forgetInstance('currentTenant');
            }
        }
    }

    /**
     * @return list<string>
     */
    private function migrationFiles(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        return collect(File::files($path))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.php'))
            ->map(fn ($file) => $file->getBasename('.php'))
            ->sort()
            ->values()
            ->all();
    }

    private function safeLogBasename(string $file): string
    {
        $base = basename(str_replace('\\', '/', $file));

        if ($base === '' || ! preg_match('/^[A-Za-z0-9._-]+\.log$/', $base)) {
            abort(422, 'Invalid log file name.');
        }

        $full = storage_path('logs/'.$base);
        $logsRoot = realpath(storage_path('logs')) ?: storage_path('logs');
        $real = realpath($full);

        if ($real !== false && ! str_starts_with($real, $logsRoot)) {
            abort(422, 'Invalid log file path.');
        }

        return $base;
    }

    /**
     * Tail a file without loading the entire contents into memory.
     *
     * @return array{lines: list<string>, truncated: bool}
     */
    private function tailFile(string $path, int $lineCount): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return ['lines' => [], 'truncated' => false];
        }

        try {
            $buffer = '';
            $chunkSize = 4096;
            $pos = filesize($path) ?: 0;
            $found = 0;

            while ($pos > 0 && $found <= $lineCount) {
                $read = min($chunkSize, $pos);
                $pos -= $read;
                fseek($handle, $pos);
                $chunk = fread($handle, $read);
                if ($chunk === false) {
                    break;
                }
                $buffer = $chunk.$buffer;
                $found = substr_count($buffer, "\n");
            }

            $parts = preg_split("/\r\n|\n|\r/", $buffer) ?: [];
            // Drop possible empty last element from trailing newline.
            if ($parts !== [] && end($parts) === '') {
                array_pop($parts);
            }

            $truncated = count($parts) > $lineCount;
            $lines = array_slice($parts, -$lineCount);

            return [
                'lines' => array_values($lines),
                'truncated' => $truncated || $pos > 0,
            ];
        } finally {
            fclose($handle);
        }
    }
}
