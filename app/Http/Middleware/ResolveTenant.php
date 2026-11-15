<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(private TenantManager $tenants) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('tenant');

        // Super-admin / root routes have no string {tenant} slug.
        if (! is_string($slug) || $slug === '') {
            config([
                'session.cookie' => 'bidadmin_super_session',
                'session.connection' => 'mysql',
            ]);

            return $next($request);
        }

        $tenant = Tenant::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (! $tenant) {
            abort(404);
        }

        $this->tenants->switchTo($tenant);
        URL::defaults(['tenant' => $slug]);
        $request->route()?->forgetParameter('tenant');

        $minutes = (int) \App\Models\Setting::get('session_lifetime', config('session.lifetime'));
        if ($minutes > 0) {
            config(['session.lifetime' => $minutes]);
        }

        return $next($request);
    }
}
