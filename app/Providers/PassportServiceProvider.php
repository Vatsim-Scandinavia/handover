<?php

namespace App\Providers;

use App\OAuth\AccessToken;
use App\OAuth\SigningKeys\PublishedKeysValidator;
use App\OAuth\SigningKeys\SigningKeyStore;
use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider as BaseServiceProvider;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\ResourceServer;

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
        $this->app->scoped(SigningKeyStore::class, fn () => new SigningKeyStore(storage_path('oauth-keys')));

        Passport::useAccessTokenEntity(AccessToken::class);
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

    protected function registerResourceServer(): void
    {
        $this->app->scoped(ResourceServer::class, function ($container) {
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
