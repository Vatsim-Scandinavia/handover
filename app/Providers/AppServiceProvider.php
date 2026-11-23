<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\OAuth\AuthorizationController;
use App\OAuth\ScopeRepository;
use App\Vatsim\VatsimConnect;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\ScopeRepository as PassportScopeRepository;
use Laravel\Passport\Http\Controllers\AuthorizationController as PassportAuthorizationController;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enforce each client's policy. See App\Models\Passport\ClientPolicy.
        $this->app->bind(PassportScopeRepository::class, ScopeRepository::class);
        $this->app->bind(PassportAuthorizationController::class, AuthorizationController::class);
        $this->app->when(AuthorizationController::class)
            ->needs(StatefulGuard::class)
            ->give(fn () => Auth::guard(config('passport.guard')));

        $this->app->when(VatsimConnect::class)
            ->needs(AbstractProvider::class)
            ->give(function (): GenericProvider {
                $base = config()->string('vatsim_auth.base');

                return new GenericProvider([
                    'clientId' => config('vatsim_auth.id'),
                    'clientSecret' => config('vatsim_auth.secret'),
                    'redirectUri' => route('login'),
                    'urlAuthorize' => $base.'/oauth/authorize',
                    'urlAccessToken' => $base.'/oauth/token',
                    'urlResourceOwnerDetails' => $base.'/api/user',
                    'scopes' => config('vatsim_auth.scopes'),
                    'scopeSeparator' => ' ',
                ]);
            });
        $this->app->when(VatsimConnect::class)
            ->needs('$requiredScopes')
            ->giveConfig('vatsim_auth.scopes');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
