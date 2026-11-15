<?php

namespace App\Console\Commands;

use App\Services\TenantManager;
use Illuminate\Console\Command;

class TenantsCreate extends Command
{
    protected $signature = 'tenants:create {slug} {email} {password} {--name=}';

    protected $description = 'Create a new tenant database and admin user';

    public function handle(TenantManager $tenants): int
    {
        $slug = (string) $this->argument('slug');
        $name = (string) ($this->option('name') ?: ucfirst($slug));

        $tenant = $tenants->create(
            $name,
            $slug,
            (string) $this->argument('email'),
            (string) $this->argument('password'),
        );

        $this->info("Tenant [{$tenant->slug}] created with database [{$tenant->db_name}].");

        return self::SUCCESS;
    }
}
