<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantsImportLegacy extends Command
{
    protected $signature = 'tenants:import-legacy {slug} {--name=} {--source=}';

    protected $description = 'Create a tenant and copy data from the legacy single database';

    public function handle(TenantManager $tenants): int
    {
        if ($tenants->centralDriver() !== 'mysql') {
            $this->error('tenants:import-legacy requires DB_CONNECTION=mysql.');

            return self::FAILURE;
        }

        $slug = (string) $this->argument('slug');
        $name = (string) ($this->option('name') ?: ucfirst($slug));
        $source = (string) ($this->option('source') ?: $tenants->centralDatabase());

        $tenants->validateSlug($slug);

        if (Tenant::withTrashed()->where('slug', $slug)->exists()) {
            $this->error('Slug already exists.');

            return self::FAILURE;
        }

        $dbName = Tenant::dbNameFor($slug);
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'db_name' => $dbName,
            'status' => 'active',
        ]);

        $tenants->createDatabase($dbName);
        $tenants->switchTo($tenant);
        $tenants->migrateTenant();

        $central = $tenants->centralConnection();
        $tables = collect(DB::connection($central)->select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->reject(fn ($t) => in_array($t, ['migrations', 'super_admins', 'tenants'], true))
            ->filter(fn ($t) => Schema::connection('tenant')->hasTable($t))
            ->values();

        DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            $rows = DB::connection($central)->table($table)->get();
            if ($rows->isEmpty()) {
                continue;
            }
            foreach ($rows->chunk(200) as $chunk) {
                DB::connection('tenant')->table($table)->insert(
                    $chunk->map(fn ($r) => (array) $r)->all()
                );
            }
            $this->line("Copied {$rows->count()} rows into {$table}");
        }
        DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info("Legacy data imported into tenant [{$slug}] from [{$source}].");

        return self::SUCCESS;
    }
}
