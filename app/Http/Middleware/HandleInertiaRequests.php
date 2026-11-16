<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        // Super routes must not inherit a lingering tenant context (tests / prior switchTo).
        $onSuper = $request->is('super') || $request->is('super/*');
        $tenant = $onSuper ? null : app(TenantManager::class)->current();

        // Never use the default guard: actingAs(..., 'super') calls shouldUse('super'),
        // so user() would return SuperAdmin (no role / isAdmin()).
        $webUser = $request->user('web');
        $user = $webUser instanceof User ? $webUser : null;
        $super = $request->user('super');

        $shared = array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
                    'role' => $user->role, 'customer_id' => $user->customer_id,
                    'email_verified_at' => $user->email_verified_at, 'created_at' => $user->created_at, 'updated_at' => $user->updated_at,
                ] : null,
                'super' => $super ? [
                    'id' => $super->id, 'name' => $super->name, 'email' => $super->email,
                ] : null,
            ],
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ] : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'token' => fn () => $request->session()->get('token'),
                'migrate_output' => fn () => $request->session()->get('migrate_output'),
                'migrate_scope' => fn () => $request->session()->get('migrate_scope'),
            ],
        ]);

        if (! $tenant) {
            return array_merge($shared, [
                'name' => config('app.name'),
                'settings' => [
                    'company_name' => config('app.name'),
                    'logo_url' => null,
                    'brand_color' => '#111827',
                    'timezone' => config('app.timezone', 'UTC'),
                    'low_data_mode' => false,
                    'language' => 'en',
                    'currency' => 'USD',
                ],
                'balance' => null,
            ]);
        }

        $s = Setting::all_settings();

        return array_merge($shared, [
            'name' => $s['company_name'] ?: config('app.name'),
            'settings' => [
                'company_name' => $s['company_name'],
                'logo_url' => $s['logo_path'] ? Storage::disk('public')->url($s['logo_path']) : null,
                'brand_color' => $s['brand_color'],
                'timezone' => $s['timezone'],
                'low_data_mode' => $s['low_data_mode'] === '1',
                'language' => $s['language'],
                'currency' => $s['currency'],
            ],
            'balance' => fn () => $user?->isAdmin() ? Transaction::balance() : null,
        ]);
    }
}
