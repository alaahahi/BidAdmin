<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Services\TenantManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantManager::class);
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        RateLimiter::for('mcp', function (Request $request) {
            $tokenId = $request->user()?->currentAccessToken()?->id;

            return Limit::perMinute(60)->by($tokenId ? 'mcp-token:'.$tokenId : 'mcp-ip:'.$request->ip());
        });

        RateLimiter::for('app-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
