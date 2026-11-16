<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use App\Models\User;
use App\Services\SystemOpsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Avoids App\Tests\TestCase::bootTenant (fragile on file sqlite after migrate:fresh).
 */
class SuperSystemSettingsTest extends BaseTestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    protected $connectionsToTransact = ['sqlite', 'central', 'tenant'];

    private function makeSuper(): SuperAdmin
    {
        return SuperAdmin::query()->create([
            'name' => 'Super',
            'email' => 'ops-'.uniqid('', true).'@bidadmin.test',
            'password' => 'password',
        ]);
    }

    private function makeTenantAdmin(): User
    {
        $admin = new User([
            'name' => 'Tenant Admin',
            'email' => 'tenant-admin@bidadmin.test',
            'role' => 'admin',
        ]);
        $admin->id = 999;
        $admin->exists = true;

        return $admin;
    }

    /**
     * @return array{
     *     separate_databases: bool,
     *     central: array{ran: int, pending: int, last: ?string},
     *     tenant: array{ran: int, pending: int, last: ?string, tenant_count: int}
     * }
     */
    private function fakeMigrationStatus(): array
    {
        return [
            'separate_databases' => false,
            'central' => ['ran' => 3, 'pending' => 0, 'last' => '2026_11_07_200001_create_tenants_table'],
            'tenant' => ['ran' => 5, 'pending' => 0, 'last' => '2026_11_16_030100_create_auction_sessions_table', 'tenant_count' => 1],
        ];
    }

    private function stubOpsForPage(?string $logFile = 'laravel.log'): void
    {
        $ops = $this->mock(SystemOpsService::class);
        $ops->shouldReceive('migrationStatus')->andReturn($this->fakeMigrationStatus());
        $ops->shouldReceive('listLogFiles')->andReturn([
            ['name' => 'laravel.log', 'path' => 'laravel.log', 'size' => 12, 'modified_at' => now()->toDateTimeString()],
        ]);
        $ops->shouldReceive('readLogTail')->andReturn([
            'file' => $logFile,
            'exists' => true,
            'size' => 12,
            'lines' => ['line-one', 'line-two'],
            'truncated' => false,
        ]);
    }

    public function test_guest_cannot_access_system_settings(): void
    {
        $this->get(route('super.system-settings'))
            ->assertRedirect(route('super.login'));
    }

    public function test_tenant_admin_cannot_access_system_settings(): void
    {
        $this->actingAs($this->makeTenantAdmin(), 'web')
            ->get(route('super.system-settings'))
            ->assertRedirect(route('super.login'));
    }

    public function test_super_admin_can_view_system_settings(): void
    {
        $this->stubOpsForPage();

        $this->actingAs($this->makeSuper(), 'super')
            ->get(route('super.system-settings'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('super/system-settings')
                ->has('migrationStatus')
                ->has('logFiles')
                ->has('log')
                ->has('lines')
            );
    }

    public function test_guest_cannot_run_migrations(): void
    {
        $this->post(route('super.system-settings.migrate'), ['scope' => 'central'])
            ->assertRedirect(route('super.login'));
    }

    public function test_tenant_admin_cannot_run_migrations(): void
    {
        $this->actingAs($this->makeTenantAdmin(), 'web')
            ->post(route('super.system-settings.migrate'), ['scope' => 'central'])
            ->assertRedirect(route('super.login'));
    }

    public function test_super_admin_can_run_central_migrations(): void
    {
        $ops = $this->mock(SystemOpsService::class);
        $ops->shouldReceive('runCentralMigrations')->once()->andReturn([
            'exit_code' => 0,
            'output' => 'Nothing to migrate.',
        ]);

        $this->actingAs($this->makeSuper(), 'super')
            ->from(route('super.system-settings'))
            ->post(route('super.system-settings.migrate'), ['scope' => 'central'])
            ->assertRedirect(route('super.system-settings'))
            ->assertSessionHas('migrate_output', 'Nothing to migrate.')
            ->assertSessionHas('migrate_scope', 'central');
    }

    public function test_super_admin_can_run_tenant_migrations(): void
    {
        $ops = $this->mock(SystemOpsService::class);
        $ops->shouldReceive('runTenantMigrations')->once()->andReturn([
            'exit_code' => 0,
            'output' => 'Migrated tenants.',
        ]);

        $this->actingAs($this->makeSuper(), 'super')
            ->from(route('super.system-settings'))
            ->post(route('super.system-settings.migrate'), ['scope' => 'tenant'])
            ->assertRedirect(route('super.system-settings'))
            ->assertSessionHas('migrate_output', 'Migrated tenants.')
            ->assertSessionHas('migrate_scope', 'tenant');
    }

    public function test_migrate_requires_valid_scope(): void
    {
        $this->actingAs($this->makeSuper(), 'super')
            ->post(route('super.system-settings.migrate'), ['scope' => 'all'])
            ->assertSessionHasErrors('scope');
    }

    public function test_super_admin_can_read_log_tail_safely(): void
    {
        $this->stubOpsForPage('laravel.log');

        $this->actingAs($this->makeSuper(), 'super')
            ->get(route('super.system-settings', ['file' => 'laravel.log', 'lines' => 100]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('super/system-settings')
                ->where('log.file', 'laravel.log')
                ->where('log.exists', true)
                ->has('log.lines')
            );
    }

    public function test_path_traversal_log_file_is_rejected(): void
    {
        $this->actingAs($this->makeSuper(), 'super')
            ->get(route('super.system-settings', ['file' => '../.env']))
            ->assertSessionHasErrors('file');
    }

    public function test_super_admin_can_download_log(): void
    {
        $ops = $this->mock(SystemOpsService::class);
        $ops->shouldReceive('downloadLog')->once()->andReturn(
            new StreamedResponse(function () {
                echo "download-me\n";
            }, 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename=laravel.log',
            ])
        );

        $this->actingAs($this->makeSuper(), 'super')
            ->get(route('super.system-settings.logs.download', ['file' => 'laravel.log']))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_guest_cannot_download_log(): void
    {
        $this->get(route('super.system-settings.logs.download', ['file' => 'laravel.log']))
            ->assertRedirect(route('super.login'));
    }
}
