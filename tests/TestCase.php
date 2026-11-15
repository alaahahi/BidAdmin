<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    protected string $tenantSlug = 'demo';

    /** @var list<string> */
    protected array $tenantAppRoots = [
        'login', 'logout', 'forgot-password', 'reset-password', 'verify-email', 'confirm-password',
        'dashboard', 'accounts', 'invoices', 'customers', 'brokers', 'finance', 'explorer',
        'shipping-rates', 'quotations', 'numbers', 'settings', 'q',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->app->environment('testing') && config('database.default') === 'mysql') {
            $this->bootTenant();
        }
    }

    protected function bootTenant(): void
    {
        $manager = app(TenantManager::class);

        Tenant::withTrashed()->where('slug', $this->tenantSlug)->forceDelete();

        if ($manager->separateDatabases()) {
            $dbName = Tenant::dbNameFor($this->tenantSlug);
            DB::connection('mysql')->statement("DROP DATABASE IF EXISTS `{$dbName}`");
            $manager->create('Demo', $this->tenantSlug, 'admin@bidadmin.test', 'password');
        } else {
            $this->ensureSharedTenantSchema($manager);

            $tenant = Tenant::create([
                'name' => 'Demo',
                'slug' => $this->tenantSlug,
                'db_name' => $manager->centralDatabase(),
                'admin_email' => 'admin@bidadmin.test',
                'status' => 'active',
            ]);
            $manager->switchTo($tenant);
            User::on('tenant')->updateOrCreate(
                ['email' => 'admin@bidadmin.test'],
                [
                    'name' => 'Admin',
                    'password' => 'password',
                    'role' => 'admin',
                    'email_verified_at' => now(),
                ],
            );
        }

        URL::defaults(['tenant' => $this->tenantSlug]);
        config(['database.default' => 'tenant']);
    }

    /**
     * Install app tables into the shared testing DB once per process.
     * Commits the open RefreshDatabase transaction first so MySQL DDL does not hang.
     */
    private function ensureSharedTenantSchema(TenantManager $manager): void
    {
        config(['database.connections.tenant.database' => $manager->centralDatabase()]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        if (Schema::connection('tenant')->hasTable('customers')) {
            return;
        }

        try {
            DB::connection('mysql')->unprepared('COMMIT');
        } catch (\Throwable) {
            //
        }

        $manager->migrateTenant();
        DB::connection('mysql')->beginTransaction();
    }

    protected function tenantUrl(string $uri): string
    {
        if ($uri === '' || $uri[0] !== '/') {
            return $uri;
        }

        if ($uri === '/' || str_starts_with($uri, '/super') || str_starts_with($uri, '/up')) {
            return $uri;
        }

        $path = parse_url($uri, PHP_URL_PATH) ?: $uri;
        $first = explode('/', ltrim($path, '/'))[0] ?? '';

        if ($first === $this->tenantSlug) {
            return $uri;
        }

        if ($first === 'api') {
            return '/'.$this->tenantSlug.$uri;
        }

        if (! in_array($first, $this->tenantAppRoots, true)) {
            return $uri;
        }

        return '/'.$this->tenantSlug.$uri;
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        return parent::call($method, $this->tenantUrl($uri), $parameters, $cookies, $files, $server, $content);
    }

    public function from($uri)
    {
        return parent::from($this->tenantUrl($uri));
    }
}
