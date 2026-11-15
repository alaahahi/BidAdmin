<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MultiTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_tenant(): void
    {
        SuperAdmin::query()->create([
            'name' => 'Super',
            'email' => 'superadmin@bidadmin.test',
            'password' => 'password',
        ]);

        $manager = app(TenantManager::class);
        if ($manager->separateDatabases()) {
            DB::connection('mysql')->statement('DROP DATABASE IF EXISTS `bidadmin_acme`');
        }
        Tenant::withTrashed()->where('slug', 'acme')->forceDelete();
        User::on('tenant')->where('email', 'admin@acme.test')->delete();

        // Shared mode: TenantManager::create skips migrate when schema exists.
        $this->actingAs(SuperAdmin::first(), 'super')
            ->post('/super/tenants', [
                'name' => 'Acme Bids',
                'slug' => 'acme',
                'admin_email' => 'admin@acme.test',
                'admin_password' => 'password123',
            ])
            ->assertRedirect(route('super.dashboard'));

        $this->assertDatabaseHas('tenants', ['slug' => 'acme', 'status' => 'active'], 'mysql');

        if ($manager->separateDatabases()) {
            $this->assertTrue(
                collect(DB::connection('mysql')->select("SHOW DATABASES LIKE 'bidadmin_acme'"))->isNotEmpty()
            );
        } else {
            $this->assertDatabaseHas('tenants', [
                'slug' => 'acme',
                'db_name' => $manager->centralDatabase(),
            ], 'mysql');
        }

        $manager->switchTo(Tenant::where('slug', 'acme')->firstOrFail());
        $this->assertNotNull(User::on('tenant')->where('email', 'admin@acme.test')->first());
    }

    public function test_tenant_isolation_when_separate_databases_enabled(): void
    {
        $manager = app(TenantManager::class);
        if (! $manager->separateDatabases()) {
            $this->markTestSkipped('Isolation requires TENANCY_SEPARATE_DATABASES=true');
        }

        DB::connection('mysql')->statement('DROP DATABASE IF EXISTS `bidadmin_alpha`');
        DB::connection('mysql')->statement('DROP DATABASE IF EXISTS `bidadmin_beta`');
        Tenant::withTrashed()->whereIn('slug', ['alpha', 'beta'])->forceDelete();

        $manager->create('Alpha', 'alpha', 'alpha@test.com', 'password');
        $manager->create('Beta', 'beta', 'beta@test.com', 'password');

        URL::defaults(['tenant' => 'alpha']);
        config(['database.default' => 'tenant']);
        $manager->switchTo(Tenant::where('slug', 'alpha')->firstOrFail());

        $this->post('/alpha/login', [
            'email' => 'beta@test.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->post('/alpha/login', [
            'email' => 'alpha@test.com',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticated('web');
    }

    public function test_super_can_enter_system_in_shared_mode(): void
    {
        $manager = app(TenantManager::class);
        if ($manager->separateDatabases()) {
            $this->markTestSkipped('Enter-system sync is for shared DB mode');
        }

        $super = SuperAdmin::query()->create([
            'name' => 'Super',
            'email' => 'superadmin@bidadmin.test',
            'password' => 'password',
        ]);

        $this->assertNotNull(Tenant::where('slug', 'demo')->first());

        $this->actingAs($super, 'super')
            ->post(route('super.enter'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated('web');
        $this->assertTrue(auth('web')->user()->isSuperAdmin());
    }

    public function test_unknown_tenant_slug_returns_404(): void
    {
        $this->get('/no-such-tenant/login')->assertNotFound();
        $this->get('/no-such-tenant/dashboard')->assertNotFound();
    }
}
