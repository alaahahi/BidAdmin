<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function create()
    {
        return Inertia::render('super/login');
    }

    public function store(Request $request, TenantManager $tenants)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('super')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        /** @var SuperAdmin $super */
        $super = $request->user('super');
        $this->mirrorSuperIntoUsers($super, $tenants);

        return redirect()->intended(route('super.dashboard'));
    }

    public function enter(Request $request, TenantManager $tenants)
    {
        /** @var SuperAdmin $super */
        $super = $request->user('super');
        abort_unless($super, 403);

        $tenant = Tenant::query()->where('status', 'active')->orderBy('id')->firstOrFail();
        $tenants->switchTo($tenant);
        URL::defaults(['tenant' => $tenant->slug]);

        $user = $this->mirrorSuperIntoUsers($super, $tenants);
        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        Auth::guard('super')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('super.login');
    }

    private function mirrorSuperIntoUsers(SuperAdmin $super, TenantManager $tenants): User
    {
        $tenant = Tenant::query()->where('status', 'active')->orderBy('id')->first();
        if ($tenant) {
            $tenants->switchTo($tenant);
        } else {
            config(['database.connections.tenant.database' => $tenants->centralDatabase()]);
            DB::purge('tenant');
            DB::reconnect('tenant');
        }

        $hash = $super->getRawOriginal('password');

        if ($user = User::on('tenant')->where('email', $super->email)->first()) {
            $user->forceFill([
                'name' => $super->name,
                'role' => 'super_admin',
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            $id = DB::connection('tenant')->table('users')->insertGetId([
                'name' => $super->name,
                'email' => $super->email,
                'password' => $hash,
                'role' => 'super_admin',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $user = User::on('tenant')->findOrFail($id);
        }

        // Keep password in sync without double-hashing the cast.
        DB::connection('tenant')->table('users')->where('id', $user->id)->update([
            'password' => $hash,
        ]);

        return $user->fresh();
    }
}
