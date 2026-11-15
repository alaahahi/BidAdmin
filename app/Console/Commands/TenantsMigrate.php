<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantManager;
use Illuminate\Console\Command;

class TenantsMigrate extends Command
{
    protected $signature = 'tenants:migrate';

    protected $description = 'Run tenant migrations for all tenants';

    public function handle(TenantManager $tenants): int
    {
        $all = Tenant::withTrashed()->get();

        if ($all->isEmpty()) {
            $this->warn('No tenants found.');

            return self::SUCCESS;
        }

        foreach ($all as $tenant) {
            $this->info("Migrating {$tenant->slug} ({$tenant->db_name})...");
            $tenants->switchTo($tenant);
            $tenants->migrateTenant();
            $this->line(trim(\Artisan::output()));
        }

        return self::SUCCESS;
    }
}
