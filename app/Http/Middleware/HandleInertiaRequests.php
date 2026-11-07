<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\Transaction;
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
        $s = Setting::all_settings();
        $user = $request->user();

        return array_merge(parent::share($request), [
            'name' => $s['company_name'] ?: config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
                    'role' => $user->role, 'customer_id' => $user->customer_id,
                    'email_verified_at' => $user->email_verified_at, 'created_at' => $user->created_at, 'updated_at' => $user->updated_at,
                ] : null,
            ],
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
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'token' => fn () => $request->session()->get('token'),
            ],
        ]);
    }
}
