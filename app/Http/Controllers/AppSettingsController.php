<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AppSettingsController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();

        return Inertia::render('settings/index', [
            'tab' => $r->input('tab', 'branding'),
            'values' => Setting::all_settings(),
            'timezones' => \DateTimeZone::listIdentifiers(),
            'tokens' => $user->tokens()->latest()->get(['id', 'name', 'abilities', 'last_used_at', 'created_at', 'expires_at'])->map(fn ($t) => $t->toArray() + ['preview' => 'ba_'.str_repeat('•', 8).$t->id]),
            'team' => User::where('role', 'admin')->orderBy('name')->get(['id', 'name', 'email', 'created_at']),
        ]);
    }

    public function update(Request $r)
    {
        $section = $r->input('section');
        $rules = match ($section) {
            'branding' => ['company_name' => ['required', 'string', 'max:100'], 'brand_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/']],
            'app' => [
                'currency' => ['required', Rule::in(['USD', 'CAD', 'EUR', 'GBP', 'AED', 'IQD'])],
                'customer_portal' => ['boolean'],
                'customers_can_see_invoices' => ['boolean'],
                'default_per_page' => ['required', Rule::in(['10', '25', '50', '100', 10, 25, 50, 100])],
                'session_lifetime' => ['required', 'integer', 'min:5', 'max:525600'],
                'blocked_countries' => ['nullable', 'string', 'max:200'],
            ],
            'timezone' => ['timezone' => ['required', 'timezone'], 'invoice_timezone' => ['required', Rule::in(['account', 'invoice'])]],
            'performance' => ['low_data_mode' => ['boolean']],
            'language' => ['language' => ['required', Rule::in(['en', 'ar', 'ckb'])]],
            default => abort(422, 'Unknown section'),
        };
        Setting::put($r->validate($rules));

        return back()->with('success', 'Settings saved.');
    }

    public function logo(Request $r)
    {
        $r->validate(['logo' => ['required', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048']]);
        $old = Setting::get('logo_path');
        if ($old) {
            Storage::disk('public')->delete($old);
        }
        Setting::put(['logo_path' => $r->file('logo')->store('branding', 'public')]);

        return back()->with('success', 'Logo uploaded.');
    }

    public function removeLogo()
    {
        if ($old = Setting::get('logo_path')) {
            Storage::disk('public')->delete($old);
        }
        Setting::put(['logo_path' => null]);

        return back()->with('success', 'Logo removed.');
    }

    public function createToken(Request $r)
    {
        $data = $r->validate(['name' => ['required', 'string', 'max:60'], 'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:3650']]);
        $token = $r->user()->createToken($data['name'], ['*'], isset($data['expires_in_days']) ? now()->addDays((int) $data['expires_in_days']) : null);

        return back()->with('token', $token->plainTextToken)->with('success', 'API key created. Copy it now, it will not be shown again.');
    }

    public function revokeToken(Request $r, int $token)
    {
        $r->user()->tokens()->where('id', $token)->delete();

        return back()->with('success', 'API key revoked.');
    }

    public function addMember(Request $r)
    {
        $data = $r->validate(['name' => ['required', 'string', 'max:190'], 'email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'string', 'min:8']]);
        User::create($data + ['role' => 'admin', 'email_verified_at' => now()]);

        return back()->with('success', 'Team member added.');
    }

    public function removeMember(Request $r, User $user)
    {
        abort_if($user->id === $r->user()->id, 422, 'You cannot remove yourself.');
        abort_unless($user->isAdmin(), 404);
        $user->delete();

        return back()->with('success', 'Team member removed.');
    }
}
