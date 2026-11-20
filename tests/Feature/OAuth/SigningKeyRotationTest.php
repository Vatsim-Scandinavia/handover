<?php

namespace Tests\Feature\OAuth;

use App\OAuth\SigningKeys\KeyState;
use Tests\TestCase;

class SigningKeyRotationTest extends TestCase
{
    public function test_generate_stages_a_2048_bit_key(): void
    {
        $kid = $this->generateKey();

        $key = $this->signingKeys()->find($kid);
        $this->assertSame(KeyState::Staged, $key->state);
        $this->assertSame(2048, openssl_pkey_get_details(openssl_pkey_get_private($key->privateKeyPem))['bits']);
    }

    public function test_activate_refuses_until_the_key_has_been_staged_for_an_hour(): void
    {
        $original = $this->signingKeys()->active()->kid;
        $kid = $this->generateKey();

        $this->travel(59)->minutes();
        $this->artisan('signing-keys:activate', ['kid' => $kid])->assertFailed();
        $this->assertSame($original, $this->signingKeys()->active()->kid);

        $this->travel(1)->minutes();
        $this->artisan('signing-keys:activate', ['kid' => $kid])->assertSuccessful();
        $this->assertSame($kid, $this->signingKeys()->active()->kid);
        $this->assertSame(KeyState::Retired, $this->signingKeys()->find($original)->state);
    }

    public function test_activate_with_force_skips_the_wait(): void
    {
        $kid = $this->generateKey();

        $this->artisan('signing-keys:activate', ['kid' => $kid, '--force' => true])->assertSuccessful();

        $this->assertSame($kid, $this->signingKeys()->active()->kid);
    }

    public function test_activate_accepts_only_a_staged_key(): void
    {
        $active = $this->signingKeys()->active()->kid;

        $this->artisan('signing-keys:activate', ['kid' => $active, '--force' => true])->assertFailed();
        $this->artisan('signing-keys:activate', ['kid' => 'no-such-kid', '--force' => true])->assertFailed();

        $this->artisan('signing-keys:activate', ['kid' => $this->generateKey(), '--force' => true])->assertSuccessful();
        $this->artisan('signing-keys:activate', ['kid' => $active, '--force' => true])->assertFailed();
    }

    public function test_remove_refuses_until_the_key_has_been_retired_for_two_hours(): void
    {
        $original = $this->signingKeys()->active()->kid;
        $this->activateKey($this->generateKey());

        $this->travel(119)->minutes();
        $this->artisan('signing-keys:remove', ['kid' => $original])->assertFailed();
        $this->assertNotNull($this->signingKeys()->find($original));

        $this->travel(1)->minutes();
        $this->artisan('signing-keys:remove', ['kid' => $original])->assertSuccessful();
        $this->assertNull($this->signingKeys()->find($original));
    }

    public function test_remove_with_force_skips_the_wait(): void
    {
        $original = $this->signingKeys()->active()->kid;
        $this->activateKey($this->generateKey());

        $this->artisan('signing-keys:remove', ['kid' => $original, '--force' => true])->assertSuccessful();

        $this->assertNull($this->signingKeys()->find($original));
    }

    public function test_remove_refuses_active_and_staged_keys_even_with_force(): void
    {
        $this->artisan('signing-keys:remove', ['kid' => $this->signingKeys()->active()->kid, '--force' => true])->assertFailed();
        $this->artisan('signing-keys:remove', ['kid' => $staged = $this->generateKey(), '--force' => true])->assertFailed();

        $this->assertNotNull($this->signingKeys()->find($staged));
    }

    public function test_revoke_drops_a_staged_or_retired_key_and_keeps_the_active_key(): void
    {
        $original = $this->signingKeys()->active()->kid;
        $active = $this->generateKey();
        $this->activateKey($active);
        $staged = $this->generateKey();

        $this->artisan('signing-keys:revoke', ['kid' => $original])->assertSuccessful();
        $this->artisan('signing-keys:revoke', ['kid' => $staged])->assertSuccessful();

        $this->assertSame([$active], $this->storedKids());
    }

    public function test_revoking_the_active_key_activates_a_fresh_key_at_once(): void
    {
        $original = $this->signingKeys()->active()->kid;
        $staged = $this->generateKey();

        $this->artisan('signing-keys:revoke', ['kid' => $original])->assertSuccessful();

        $active = $this->signingKeys()->active();
        $this->assertNotContains($active->kid, [$original, $staged]);
        $this->assertNull($this->signingKeys()->find($original));
        $this->assertSame(KeyState::Staged, $this->signingKeys()->find($staged)->state);
        $this->assertFileDoesNotExist($this->keyDirectory."/oauth-keys/{$original}.key");
    }

    public function test_revoke_refuses_an_unknown_kid(): void
    {
        $this->artisan('signing-keys:revoke', ['kid' => 'no-such-kid'])->assertFailed();
    }

    public function test_list_shows_each_key_and_its_state(): void
    {
        $this->freezeSecond();
        $active = $this->signingKeys()->active()->kid;
        $staged = $this->generateKey();
        $since = now()->toIso8601String();

        $this->artisan('signing-keys:list')
            ->expectsTable(['Kid', 'State', 'Since', 'Imported'], [
                [$active, 'active', $this->signingKeys()->active()->since->toIso8601String(), 'yes'],
                [$staged, 'staged', $since, 'no'],
            ])
            ->assertSuccessful();
    }

    public function test_key_files_are_readable_by_their_owner_only(): void
    {
        $kid = $this->generateKey();

        $this->assertSame('0600', substr(sprintf('%o', fileperms($this->keyDirectory."/oauth-keys/{$kid}.key")), -4));
    }
}
