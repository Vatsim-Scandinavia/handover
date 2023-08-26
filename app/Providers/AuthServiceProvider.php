<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
use App\Models\Passport\Client;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {

        // Don't be fooled, we return ALL data from OAuthUserController.php without checking for requested tokens.
        // We only initiate tokens here so we can use Handover as if it were Vatsim Connect and tokens are passed.
        Passport::tokensCan([
            'full_name' => 'First and last name',
            'email' => 'E-mail address',
            'vatsim_details' => 'Ratings and divisions',
            'country' => 'Country'
        ]);

        Passport::setDefaultScope([
            'full_name',
            'email',
            'vatsim_details',
            'country'
        ]);

        Passport::routes(function ($router) {
            $router->forAuthorization();
            Passport::tokensExpireIn(now()->addYears(50));

            $router->forAccessTokens();
            Passport::refreshTokensExpireIn(now()->addYears(50));

            //$router->forTransientTokens(); // the tokens we issue are permanent
            //$router->forClients(); // we don't want external applications using our oauth flows
            //$router->forPersonalAccessTokens(); // we don't have a user-facing API yet
        });

        Passport::useClientModel(Client::class);

        $this->registerPolicies();
    }
}
