<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            $minutes = (int) \App\Models\Setting::get('session_lifetime', config('session.lifetime'));
            if ($minutes > 0) {
                config(['session.lifetime' => $minutes]);
            }
        }
    }
}
