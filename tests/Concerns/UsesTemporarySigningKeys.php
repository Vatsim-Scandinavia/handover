<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\OAuth\SigningKeys\SigningKeyStore;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;

/**
 * Points the signing key store and Passport's key path at a temporary directory, so
 * tests never touch the real storage volume. The store starts with one active key.
 */
trait UsesTemporarySigningKeys
{
    protected string $keyDirectory;

    private static ?string $testPrivateKey = null;

    protected function useTemporarySigningKeys(Application $app): void
    {
        $this->keyDirectory = sys_get_temp_dir().'/handover-keys-'.Str::random(16);
        File::ensureDirectoryExists($this->keyDirectory, 0700);

        Passport::loadKeysFrom($this->keyDirectory);
        $store = new SigningKeyStore($this->keyDirectory.'/oauth-keys');
        $app->instance(SigningKeyStore::class, $store);
        $store->import(self::testPrivateKey());

        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($this->keyDirectory));
    }

    /** Remove every signing key, as on a fresh install. */
    protected function emptySigningKeyStore(): void
    {
        File::deleteDirectory($this->keyDirectory.'/oauth-keys');
        $this->app->instance(SigningKeyStore::class, new SigningKeyStore($this->keyDirectory.'/oauth-keys'));
    }

    protected function signingKeys(): SigningKeyStore
    {
        return resolve(SigningKeyStore::class);
    }

    /** @return list<string> */
    protected function storedKids(): array
    {
        return array_map(fn ($key) => $key->kid, $this->signingKeys()->published());
    }

    /** @return list<string> */
    protected function jwksKids(): array
    {
        return array_column($this->getJson('/.well-known/jwks.json')->json('keys'), 'kid');
    }

    /** Run signing-keys:generate and return the kid of the new staged key. */
    protected function generateKey(): string
    {
        $before = $this->storedKids();
        $this->artisan('signing-keys:generate')->assertSuccessful();

        return array_values(array_diff($this->storedKids(), $before))[0];
    }

    /** Activate a staged key, skipping the wait. */
    protected function activateKey(string $kid): void
    {
        $this->artisan('signing-keys:activate', ['kid' => $kid, '--force' => true])->assertSuccessful();
    }

    /** Remove a retired key, skipping the wait. */
    protected function removeKey(string $kid): void
    {
        $this->artisan('signing-keys:remove', ['kid' => $kid, '--force' => true])->assertSuccessful();
    }

    /** Put a 3.x key pair where Passport kept it, and return the private key. */
    protected function installLegacyKeyFiles(): string
    {
        $pem = self::newPrivateKey();
        File::put($this->keyDirectory.'/oauth-private.key', $pem);
        File::put($this->keyDirectory.'/oauth-public.key', openssl_pkey_get_details(openssl_pkey_get_private($pem))['key']);

        return $pem;
    }

    /** One RSA key per test process, so tests don't pay for key generation. */
    protected static function testPrivateKey(): string
    {
        return self::$testPrivateKey ??= self::newPrivateKey();
    }

    protected static function newPrivateKey(): string
    {
        openssl_pkey_export(openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]), $pem);

        return $pem;
    }
}
