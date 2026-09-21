<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

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
        $router = app('router');
        $router->aliasMiddleware('twofactor.verify', \App\Http\Middleware\RequireTwoFactorVerified::class);
        $router->aliasMiddleware('restrict.doctor', \App\Http\Middleware\RestrictDoctorAccess::class);
        $router->aliasMiddleware('restrict.reception', \App\Http\Middleware\RestrictReceptionAccess::class);
        $router->aliasMiddleware('restrict.nurse', \App\Http\Middleware\RestrictNurseAccess::class);

    }
}
