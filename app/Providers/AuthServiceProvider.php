<?php

namespace App\Providers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Carbon\CarbonInterval;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
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
     */
    public function boot(): void
    {
        Passport::tokensCan([
            'full_name'      => 'First and last name',
            'email'          => 'E-mail address',
            'vatsim_details' => 'Ratings and divisions',
            'country'        => 'Country',
            'groups'         => 'View the groups you are a member of',
            // OpenID Connect. See docs/integrations/oidc.md.
            'openid'         => 'Sign in with OpenID Connect',
            'profile'        => 'First and last name, for OpenID Connect',
        ]);

        // No Passport default scope: a request with no scopes gets the client
        // policy's default scopes instead. See App\OAuth\ScopeRepository.

        // See docs/adr/0001-token-lifetimes-and-refresh-policy.md. Each refresh
        // issues a new refresh token, so the 30 days restart on every use.
        Passport::tokensExpireIn(CarbonInterval::hour());
        Passport::refreshTokensExpireIn(CarbonInterval::days(30));
        Passport::useClientModel(Client::class);

        // New clients get UUIDv7 ids. Clients from before 4.0.0 keep their
        // numeric ids, stored as strings. See docs/adr/0002-client-ids-and-schema.md.
        Passport::$clientUuids = true;

        // Passport 13 is headless: AuthorizationController::authorize() method-injects
        // an AuthorizationViewResponse, which is unbound by default. Without binding
        // it, every /oauth/authorize request fails with "not instantiable". All of
        // this app's clients auto-approve (Client::skipsAuthorization), so the consent
        // screen is never rendered, but the binding must exist for the route to resolve.
        Passport::authorizationView(fn (array $parameters): ResponseFactory|Response => response(
            'This application does not require explicit authorization.'
        ));

        // Passport 13 throws its own AuthenticationException at /oauth/authorize,
        // which the auth middleware's redirectTo() never sees. Without this,
        // guests get an empty 401 instead of the login page. Elsewhere, null
        // keeps the framework default.
        AuthenticationException::redirectUsing(
            fn (Request $request): ?string => $request->routeIs('passport.authorizations.authorize')
                ? route('login')
                : null
        );

        Gate::define('manage-oauth-clients', fn (User $user): bool => app(GroupManagerService::class)->isAdmin($user));

        $this->registerPolicies();
    }
}
