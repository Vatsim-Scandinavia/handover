<?php

namespace App\Providers;

use App\Http\Controllers\OAuth\AuthorizationController;
use App\OAuth\ScopeRepository;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\ScopeRepository as PassportScopeRepository;
use Laravel\Passport\Http\Controllers\AuthorizationController as PassportAuthorizationController;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Enforce each client's policy. See App\Models\Passport\ClientPolicy.
        $this->app->bind(PassportScopeRepository::class, ScopeRepository::class);
        $this->app->bind(PassportAuthorizationController::class, AuthorizationController::class);
        $this->app->when(AuthorizationController::class)
            ->needs(StatefulGuard::class)
            ->give(fn () => Auth::guard(config('passport.guard')));
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
