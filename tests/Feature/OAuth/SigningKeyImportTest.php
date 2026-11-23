<?php

declare(strict_types=1);

namespace Tests\Feature\OAuth;

use App\OAuth\SigningKeys\Jwk;
use App\OAuth\SigningKeys\KeyState;
use Tests\TestCase;

class SigningKeyImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->emptySigningKeyStore();
    }

    public function test_it_adopts_the_passport_key_as_the_first_active_key(): void
    {
        $legacy = $this->installLegacyKeyFiles();

        $this->artisan('signing-keys:import')->assertSuccessful();

        $key = $this->signingKeys()->active();
        $this->assertSame(Jwk::thumbprint(Jwk::fromPrivateKey($legacy)), $key->kid);
        $this->assertTrue($key->imported);
        $this->assertCount(1, $this->signingKeys()->published());
        $this->assertFileDoesNotExist($this->keyDirectory.'/oauth-private.key');
        $this->assertFileDoesNotExist($this->keyDirectory.'/oauth-public.key');
    }

    public function test_it_is_idempotent(): void
    {
        $this->installLegacyKeyFiles();
        $this->artisan('signing-keys:import')->assertSuccessful();
        $first = $this->signingKeys()->published();

        $this->artisan('signing-keys:import')->assertSuccessful();

        $this->assertEquals($first, $this->signingKeys()->published());
    }

    public function test_it_leaves_a_store_that_holds_keys_alone_even_if_a_passport_key_reappears(): void
    {
        $this->artisan('signing-keys:import')->assertSuccessful();
        $kid = $this->signingKeys()->active()->kid;
        $this->installLegacyKeyFiles();

        $this->artisan('signing-keys:import')->assertSuccessful();

        $this->assertSame([$kid], $this->storedKids());
    }

    public function test_on_a_fresh_install_it_creates_and_activates_a_2048_bit_key(): void
    {
        $this->artisan('signing-keys:import')->assertSuccessful();

        $key = $this->signingKeys()->active();
        $this->assertFalse($key->imported);
        $this->assertSame(2048, openssl_pkey_get_details(openssl_pkey_get_private($key->privateKeyPem))['bits']);
    }

    public function test_it_imports_passport_private_key_once_and_warns_that_it_is_now_ignored(): void
    {
        $pem = self::newPrivateKey();
        config(['passport.private_key' => str_replace("\n", '\\n', $pem)]);

        $this->artisan('signing-keys:import')
            ->expectsOutputToContain('PASSPORT_PRIVATE_KEY is ignored')
            ->assertSuccessful();
        $kid = Jwk::thumbprint(Jwk::fromPrivateKey($pem));
        $this->assertSame($kid, $this->signingKeys()->active()->kid);

        $this->activateKey($this->generateKey());
        $this->artisan('signing-keys:import')
            ->expectsOutputToContain('PASSPORT_PRIVATE_KEY is ignored')
            ->assertSuccessful();
        $this->assertSame(KeyState::Retired, $this->signingKeys()->find($kid)->state);
    }

    public function test_the_migration_runs_the_import(): void
    {
        $legacy = $this->installLegacyKeyFiles();

        (require database_path('migrations/2026_11_19_100000_import_signing_keys.php'))->up();

        $this->assertSame(Jwk::thumbprint(Jwk::fromPrivateKey($legacy)), $this->signingKeys()->active()->kid);
    }
}
