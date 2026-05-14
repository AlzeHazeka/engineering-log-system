<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

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
        Vite::prefetch(concurrency: 3);
        Carbon::setLocale('id');

        // Admin-only capabilities (lean: user id=1 is treated as admin).
        Gate::define('manage-users', fn ($user) => ($user?->email ?? null) === config('workstation.primary_admin_email'));

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
