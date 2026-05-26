<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use App\Models\Passport\Client;
use App\Models\Group;
use App\Policies\GroupPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Group::class => GroupPolicy::class,
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
            'full_name'      => 'First and last name',
            'email'          => 'E-mail address',
            'vatsim_details' => 'Ratings and divisions',
            'country'        => 'Country',
            'groups'         => 'View the groups you are a member of',
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
