<?php

namespace App\Providers;

use Carbon\CarbonInterval;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use App\Models\Passport\Client;
use App\Models\Group;
use App\Policies\GroupPolicy;
use App\Services\GroupManagerService;
use App\User;
use Illuminate\Support\Facades\Gate;

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

        // See docs/adr/0001-token-lifetimes-and-refresh-policy.md. Each refresh
        // issues a new refresh token, so the 30 days restart on every use.
        Passport::tokensExpireIn(CarbonInterval::hour());
        Passport::refreshTokensExpireIn(CarbonInterval::days(30));
        Passport::useClientModel(Client::class);

        // This application uses the legacy Passport schema where oauth_clients.id
        // is an auto-incrementing integer. Passport 13 defaults to UUID client
        // ids, which cannot be stored in that column, so keep integer keys.
        Passport::$clientUuids = false;

        // Passport 13 is headless: AuthorizationController::authorize() method-injects
        // an AuthorizationViewResponse, which is unbound by default. Without binding
        // it, every /oauth/authorize request fails with "not instantiable". All of
        // this app's clients auto-approve (Client::skipsAuthorization), so the consent
        // screen is never rendered, but the binding must exist for the route to resolve.
        Passport::authorizationView(fn (array $parameters) => response(
            'This application does not require explicit authorization.'
        ));

        Gate::define('manage-oauth-clients', fn (User $user): bool => app(GroupManagerService::class)->isAdmin($user));

        $this->registerPolicies();
    }
}
