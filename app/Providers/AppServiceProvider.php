<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Passport 13 disables the token/client management JSON routes by
        // default. The dashboard's authorized-clients component relies on
        // GET /oauth/tokens. This must run in register() so the flag is set
        // before PassportServiceProvider::boot() registers its routes.
        Passport::$registersJsonApiRoutes = true;
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
