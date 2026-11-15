<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TenantManager
{
    public const RESERVED_SLUGS = ['super', 'api', 'login', 'build', 'storage'];

    public function separateDatabases(): bool
    {
        return (bool) config('tenancy.separate_databases');
    }

    public function centralConnection(): string
    {
        return 'central';
    }

    public function centralDriver(): string
    {
        return (string) config('database.connections.central.driver');
    }

    public function centralDatabase(): string
    {
        return (string) config('database.connections.central.database');
    }

    public function databaseNameForSlug(string $slug): string
    {
        return $this->separateDatabases()
            ? Tenant::dbNameFor($slug)
            : $this->centralDatabase();
    }

    public function validateSlug(string $slug): void
    {
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw ValidationException::withMessages([
                'slug' => 'Slug must be lowercase letters, numbers, and dashes only.',
            ]);
        }

        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            throw ValidationException::withMessages([
                'slug' => 'This slug is reserved.',
            ]);
        }
    }

    public function create(string $name, string $slug, string $adminEmail, string $adminPassword): Tenant
    {
        $this->validateSlug($slug);

        if (Tenant::withTrashed()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['slug' => 'This slug is already taken.']);
        }

        $dbName = $this->databaseNameForSlug($slug);

        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'db_name' => $dbName,
            'admin_email' => $adminEmail,
            'status' => 'active',
        ]);

        if ($this->separateDatabases()) {
            $this->createDatabase($dbName);
        }

        $this->switchTo($tenant);
        $this->migrateTenant();

        User::on('tenant')->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Admin',
                'password' => $adminPassword,
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );

        return $tenant;
    }

    public function switchTo(Tenant $tenant): void
    {
        $database = $this->separateDatabases()
            ? $tenant->db_name
            : $this->centralDatabase();

        config(['database.connections.tenant.database' => $database]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        app()->instance('currentTenant', $tenant);

        config([
            'session.connection' => 'tenant',
            'session.cookie' => 'bidadmin_'.$tenant->slug.'_session',
            'cache.stores.database.connection' => 'tenant',
            'queue.connections.database.connection' => 'tenant',
        ]);
    }

    public function createDatabase(string $dbName): void
    {
        if ($this->centralDriver() !== 'mysql') {
            throw ValidationException::withMessages([
                'slug' => 'Separate tenant databases require MySQL (DB_CONNECTION=mysql).',
            ]);
        }

        $safe = str_replace('`', '``', $dbName);
        DB::connection($this->centralConnection())->statement(
            "CREATE DATABASE IF NOT EXISTS `{$safe}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    public function migrateTenant(): void
    {
        if (! $this->separateDatabases()) {
            $this->prepareSharedSchema();

            // Shared DB: schema is global — skip migrate if app tables already exist
            // (avoids migrator lock hangs when create() runs inside HTTP tests).
            if (Schema::connection('tenant')->hasTable('customers')) {
                return;
            }
        }

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
    }

    /**
     * In shared-DB mode the central connection already has sessions (and may
     * share users/cache/jobs). Mark those Laravel defaults as migrated so the
     * tenant path can apply app tables without colliding.
     */
    private function prepareSharedSchema(): void
    {
        $batch = ((int) DB::connection('tenant')->table('migrations')->max('batch')) + 1;

        foreach ([
            '0001_01_01_000000_create_users_table',
            '0001_01_01_000001_create_cache_table',
            '0001_01_01_000002_create_jobs_table',
        ] as $migration) {
            $exists = DB::connection('tenant')->table('migrations')->where('migration', $migration)->exists();
            if ($exists) {
                continue;
            }
            DB::connection('tenant')->table('migrations')->insert([
                'migration' => $migration,
                'batch' => $batch,
            ]);
        }

        if (! Schema::connection('tenant')->hasTable('users')) {
            Schema::connection('tenant')->create('users', function ($t) {
                $t->id();
                $t->string('name');
                $t->string('email')->unique();
                $t->timestamp('email_verified_at')->nullable();
                $t->string('password');
                $t->rememberToken();
                $t->timestamps();
            });
        }

        if (! Schema::connection('tenant')->hasTable('password_reset_tokens')) {
            Schema::connection('tenant')->create('password_reset_tokens', function ($t) {
                $t->string('email')->primary();
                $t->string('token');
                $t->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::connection('tenant')->hasTable('cache')) {
            Schema::connection('tenant')->create('cache', function ($t) {
                $t->string('key')->primary();
                $t->mediumText('value');
                $t->integer('expiration');
            });
            Schema::connection('tenant')->create('cache_locks', function ($t) {
                $t->string('key')->primary();
                $t->string('owner');
                $t->integer('expiration');
            });
        }

        if (! Schema::connection('tenant')->hasTable('jobs')) {
            Schema::connection('tenant')->create('jobs', function ($t) {
                $t->id();
                $t->string('queue')->index();
                $t->longText('payload');
                $t->unsignedTinyInteger('attempts');
                $t->unsignedInteger('reserved_at')->nullable();
                $t->unsignedInteger('available_at');
                $t->unsignedInteger('created_at');
            });
            Schema::connection('tenant')->create('job_batches', function ($t) {
                $t->string('id')->primary();
                $t->string('name');
                $t->integer('total_jobs');
                $t->integer('pending_jobs');
                $t->integer('failed_jobs');
                $t->longText('failed_job_ids');
                $t->mediumText('options')->nullable();
                $t->integer('cancelled_at')->nullable();
                $t->integer('created_at');
                $t->integer('finished_at')->nullable();
            });
            Schema::connection('tenant')->create('failed_jobs', function ($t) {
                $t->id();
                $t->string('uuid')->unique();
                $t->text('connection');
                $t->text('queue');
                $t->longText('payload');
                $t->longText('exception');
                $t->timestamp('failed_at')->useCurrent();
            });
        }
    }

    public function current(): ?Tenant
    {
        return app()->bound('currentTenant') ? app('currentTenant') : null;
    }

    /**
     * @return list<array{id:int,name:string,email:string,created_at:?string}>
     */
    public function listAdmins(Tenant $tenant): array
    {
        $previous = $this->current();

        try {
            $this->switchTo($tenant);

            return User::on('tenant')
                ->whereIn('role', ['admin', 'super_admin'])
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'created_at'])
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role,
                    'created_at' => optional($u->created_at)?->toDateTimeString(),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        } finally {
            if ($previous) {
                $this->switchTo($previous);
            }
        }
    }
}
