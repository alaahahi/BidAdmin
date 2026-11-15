<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TenantController extends Controller
{
    public function index(TenantManager $tenants)
    {
        $rows = Tenant::withTrashed()->latest()->get([
            'id', 'name', 'slug', 'db_name', 'admin_email', 'status', 'created_at', 'deleted_at',
        ]);

        $payload = $rows->map(function (Tenant $tenant) use ($tenants) {
            $admins = $tenant->deleted_at || $tenant->status === 'suspended'
                ? []
                : $tenants->listAdmins($tenant);

            // Keep primary admin email on the central record if missing (legacy tenants).
            if (! $tenant->admin_email && $admins !== []) {
                $tenant->forceFill(['admin_email' => $admins[0]['email']])->save();
            }

            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'db_name' => $tenant->db_name,
                'admin_email' => $tenant->admin_email,
                'status' => $tenant->status,
                'created_at' => $tenant->created_at,
                'deleted_at' => $tenant->deleted_at,
                'admins' => $admins,
            ];
        });

        return Inertia::render('super/dashboard', [
            'tenants' => $payload,
        ]);
    }

    public function create()
    {
        return Inertia::render('super/tenants/create');
    }

    public function store(Request $request, TenantManager $tenants)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:60'],
            'admin_email' => ['required', 'email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        $tenant = $tenants->create(
            $data['name'],
            strtolower($data['slug']),
            $data['admin_email'],
            $data['admin_password'],
        );

        return redirect()->route('super.dashboard')->with('success', "Tenant [{$tenant->slug}] created.");
    }

    public function edit(Tenant $managedTenant, TenantManager $tenants)
    {
        return Inertia::render('super/tenants/edit', [
            'tenant' => $managedTenant->only(['id', 'name', 'slug', 'db_name', 'admin_email', 'status']),
            'admins' => $managedTenant->deleted_at ? [] : $tenants->listAdmins($managedTenant),
        ]);
    }

    public function update(Request $request, Tenant $managedTenant)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'suspended'])],
        ]);

        $managedTenant->update($data);

        return redirect()->route('super.dashboard')->with('success', 'Tenant updated.');
    }

    public function suspend(Tenant $managedTenant)
    {
        $managedTenant->update(['status' => 'suspended']);

        return back()->with('success', 'Tenant suspended.');
    }

    public function activate(Tenant $managedTenant)
    {
        $managedTenant->update(['status' => 'active']);

        return back()->with('success', 'Tenant activated.');
    }

    public function destroy(Tenant $managedTenant)
    {
        $managedTenant->delete();

        return redirect()->route('super.dashboard')->with('success', 'Tenant deleted (database kept).');
    }
}
