<?php

namespace Database\Seeders;

use App\Models\SuperAdmin;
use App\Services\TenantManager;
use Illuminate\Database\Seeder;

class CentralDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        SuperAdmin::query()->updateOrCreate(
            ['email' => 'superadmin@bidadmin.test'],
            ['name' => 'Super Admin', 'password' => 'password'],
        );

        $tenants = app(TenantManager::class);

        if (! \App\Models\Tenant::withTrashed()->where('slug', 'demo')->exists()) {
            $tenants->create('Demo', 'demo', 'admin@bidadmin.test', 'password');
        } else {
            $tenants->switchTo(\App\Models\Tenant::where('slug', 'demo')->firstOrFail());
        }

        $this->call(DatabaseSeeder::class);
    }
}
