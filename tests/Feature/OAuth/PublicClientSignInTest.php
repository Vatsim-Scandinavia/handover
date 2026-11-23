<?php

declare(strict_types=1);

namespace Tests\Feature\OAuth;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\RunsOAuthFlows;
use Tests\TestCase;

class PublicClientSignInTest extends TestCase
{
    use RefreshDatabase, RunsOAuthFlows;

    public function test_a_public_client_signs_in_with_s256_and_no_secret(): void
    {
        $client = $this->makeClient(confidential: false);
        $code = $this->authorizeCode(User::factory()->create(), $client, self::PKCE);

        $this->exchangeCode($client, $code, ['code_verifier' => self::CODE_VERIFIER])
            ->assertOk()
            ->assertJsonStructure(['access_token', 'refresh_token']);
    }

    public function test_a_public_client_must_send_a_code_challenge(): void
    {
        $client = $this->makeClient(confidential: false);

        $this->authorizeRequest(User::factory()->create(), $client)->assertStatus(400);
    }

    public function test_a_public_client_cannot_use_the_plain_method(): void
    {
        $client = $this->makeClient(confidential: false);

        $this->authorizeRequest(User::factory()->create(), $client, [
            'code_challenge' => self::CODE_VERIFIER,
            'code_challenge_method' => 'plain',
        ])->assertStatus(400);

        $this->authorizeRequest(User::factory()->create(), $client, ['code_challenge' => self::CODE_VERIFIER])
            ->assertStatus(400);
    }

    public function test_a_public_client_must_send_the_code_verifier(): void
    {
        $client = $this->makeClient(confidential: false);
        $code = $this->authorizeCode(User::factory()->create(), $client, self::PKCE);

        $this->exchangeCode($client, $code)->assertStatus(400);
    }

    public function test_a_public_client_refreshes_with_rotation_and_a_single_session(): void
    {
        $client = $this->makeClient(confidential: false);
        $user = User::factory()->create();
        $first = $this->exchangeCode($client, $this->authorizeCode($user, $client, self::PKCE), [
            'code_verifier' => self::CODE_VERIFIER,
        ])->json();

        $refreshed = $this->refresh($client, $first['refresh_token'])
            ->assertOk()
            ->json();

        $this->assertNotSame($first['refresh_token'], $refreshed['refresh_token']);
        // The refresh starts a new 30-day window.
        $this->assertEqualsWithDelta(30 * 24 * 3600, $this->latestRefreshTokenLifetime(), 5);
        $this->refresh($client, $first['refresh_token'])->assertStatus(400);

        // A second sign-in ends the first session.
        $this->exchangeCode($client, $this->authorizeCode($user, $client, self::PKCE), [
            'code_verifier' => self::CODE_VERIFIER,
        ])->assertOk();
        $this->refresh($client, $refreshed['refresh_token'])->assertStatus(400);
    }

    public function test_a_confidential_client_keeps_pkce_optional_and_may_use_plain(): void
    {
        $client = $this->makeClient();
        $user = User::factory()->create();

        $this->signIn($user, $client);

        $code = $this->authorizeCode($user, $client, ['code_challenge' => self::CODE_VERIFIER, 'code_challenge_method' => 'plain']);
        $this->exchangeCode($client, $code, ['code_verifier' => self::CODE_VERIFIER])->assertOk();
    }

    /** Seconds from now until the newest refresh token expires. */
    private function latestRefreshTokenLifetime(): int
    {
        return (int) round(now()->diffInSeconds(DB::table('oauth_refresh_tokens')->latest('expires_at')->value('expires_at')));
    }
}
