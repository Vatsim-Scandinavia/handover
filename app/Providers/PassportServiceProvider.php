<?php

declare(strict_types=1);

namespace App\Providers;

use App\OAuth\AccessToken;
use App\OAuth\AuthCodeGrant;
use App\OAuth\Oidc\AuthCodeRepository;
use App\OAuth\Oidc\IdTokenResponse;
use App\OAuth\SigningKeys\PublishedKeysValidator;
use App\OAuth\SigningKeys\SigningKeyStore;
use DateInterval;
use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Bridge\AuthCodeRepository as PassportAuthCodeRepository;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider as BaseServiceProvider;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\AuthCodeGrant as LeagueAuthCodeGrant;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;

/**
 * Passport, with its keys taken from the signing key store instead of
 * oauth-private.key, oauth-public.key or the PASSPORT_*_KEY variables.
 * Composer's package discovery skips Passport's own provider, so this one
 * replaces it. See docs/adr/0003-signing-keys-and-rotation.md.
 */
class PassportServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        parent::register();

        // Scoped, so a long-lived worker re-reads the store for each request or job.
        $this->app->scoped(SigningKeyStore::class, fn (): SigningKeyStore => new SigningKeyStore(storage_path('oauth-keys')));

        Passport::useAccessTokenEntity(AccessToken::class);

        // OpenID Connect: store the nonce and auth_time with each auth code.
        // makeAuthorizationServer() adds the id_token. Docs: docs/integrations/oidc.md.
        $this->app->bind(PassportAuthCodeRepository::class, AuthCodeRepository::class);
    }

    /**
     * OpenID Connect: add an id_token to the token response. A caller's own
     * response type, such as the personal access token one, takes precedence.
     */
    protected function makeAuthorizationServer(?ResponseTypeInterface $responseType = null): AuthorizationServer
    {
        return parent::makeAuthorizationServer($responseType ?? $this->app->make(IdTokenResponse::class));
    }

    /**
     * The authorization server hands its private key to every grant. AccessToken
     * signs through ActiveKeySigner instead, but the server still needs a valid key.
     */
    protected function makeCryptKey(string $type): CryptKey
    {
        if ($type !== 'private') {
            return parent::makeCryptKey($type);
        }

        return $this->app->make(SigningKeyStore::class)->active()->privateCryptKey();
    }

    /**
     * Public clients must use PKCE with S256. See App\OAuth\AuthCodeGrant.
     * The arguments match Passport's own buildAuthCodeGrant(), including the
     * 10-minute auth code lifetime.
     */
    protected function buildAuthCodeGrant(): LeagueAuthCodeGrant
    {
        return new AuthCodeGrant(
            $this->app->make(PassportAuthCodeRepository::class),
            $this->app->make(RefreshTokenRepository::class),
            new DateInterval('PT10M'),
        );
    }

    protected function registerResourceServer(): void
    {
        $this->app->scoped(ResourceServer::class, function ($container): ResourceServer {
            $tokens = $container->make(AccessTokenRepository::class);
            $keys = $container->make(SigningKeyStore::class);

            // League reads this public key only for its own validator, which the
            // custom validator replaces, but the constructor requires a valid one.
            return new ResourceServer(
                $tokens,
                $keys->active()->publicCryptKey(),
                new PublishedKeysValidator($tokens, $keys),
            );
        });
    }
}
