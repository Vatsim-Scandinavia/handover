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

        Passport::tokensExpireIn(now()->addMonths(1));
        Passport::refreshTokensExpireIn(now()->addYears(100));
        Passport::useClientModel(Client::class);

        $this->registerPolicies();
    }
}
