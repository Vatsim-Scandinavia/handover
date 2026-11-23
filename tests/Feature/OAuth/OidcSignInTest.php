<?php

declare(strict_types=1);

namespace Tests\Feature\OAuth;

use App\Models\Group;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Client;
use Tests\Concerns\RunsOAuthFlows;
use Tests\TestCase;

class OidcSignInTest extends TestCase
{
    use RefreshDatabase, RunsOAuthFlows;

    private const array OIDC_SCOPES = ['openid', 'profile', 'email', 'groups', 'full_name', 'vatsim_details', 'country'];

    public function test_an_openid_sign_in_returns_an_id_token_signed_by_a_published_key(): void
    {
        $tokens = $this->signIn(User::factory()->create(), $this->oidcClient(), ['scope' => 'openid']);

        $this->assertArrayHasKey('id_token', $tokens);
        $header = $this->jwtHeader($tokens['id_token']);
        $this->assertSame('RS256', $header['alg']);
        $this->assertSame($this->signingKeys()->active()->kid, $header['kid']);
        $this->assertJwtVerifiesAgainstTheJwks($tokens['id_token']);
    }

    public function test_the_id_token_identifies_the_member_the_client_and_the_access_token(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        $tokens = $this->signIn($user, $client, ['scope' => 'openid']);
        $claims = $this->jwtPayload($tokens['id_token']);

        $this->assertSame(rtrim(config('app.url'), '/'), $claims['iss']);
        $this->assertSame((string) $user->id, $claims['sub']);
        $this->assertSame((string) $client->getKey(), $claims['aud']);
        $this->assertIsInt($claims['iat']);
        $this->assertIsInt($claims['exp']);
        $this->assertEqualsWithDelta(time(), $claims['iat'], 5);
        $this->assertEqualsWithDelta(time() + 3600, $claims['exp'], 5);
        $this->assertSame($this->atHash($tokens['access_token']), $claims['at_hash']);
        $this->assertArrayNotHasKey('nonce', $claims);
    }

    public function test_the_nonce_round_trips(): void
    {
        $tokens = $this->signIn(User::factory()->create(), $this->oidcClient(), ['scope' => 'openid', 'nonce' => 'n-0S6_WzA2Mj']);

        $this->assertSame('n-0S6_WzA2Mj', $this->jwtPayload($tokens['id_token'])['nonce']);
    }

    public function test_the_nonce_survives_prompt_consent(): void
    {
        $tokens = $this->signIn(User::factory()->create(), $this->oidcClient(), ['scope' => 'openid', 'nonce' => 'n-0S6_WzA2Mj', 'prompt' => 'consent']);

        $this->assertSame('n-0S6_WzA2Mj', $this->jwtPayload($tokens['id_token'])['nonce']);
    }

    public function test_auth_time_is_when_the_member_signed_in_to_handover(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();

        $this->travelTo(now()->subMinutes(10));
        Auth::login($user);
        $signedInAt = now()->getTimestamp();
        $this->travelBack();

        $tokens = $this->signIn($user, $client, ['scope' => 'openid']);

        $this->assertSame($signedInAt, $this->jwtPayload($tokens['id_token'])['auth_time']);
    }

    public function test_the_claims_follow_the_granted_scopes(): void
    {
        $user = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com']);
        $parent = Group::factory()->create();
        $child = Group::factory()->create();
        $child->parents()->attach($parent->id);
        $child->members()->attach($user->id);
        $client = $this->oidcClient();

        $all = $this->jwtPayload($this->signIn($user, $client, ['scope' => 'openid profile email groups'])['id_token']);
        $none = $this->jwtPayload($this->signIn($user, $client, ['scope' => 'openid full_name vatsim_details country'])['id_token']);

        $this->assertSame('Ada Lovelace', $all['name']);
        $this->assertSame('Ada', $all['given_name']);
        $this->assertSame('Lovelace', $all['family_name']);
        $this->assertSame('ada@example.com', $all['email']);
        $this->assertArrayNotHasKey('email_verified', $all);
        $this->assertEqualsCanonicalizing([$child->id, $parent->id], $all['groups']);

        foreach (['name', 'given_name', 'family_name', 'email', 'groups'] as $claim) {
            $this->assertArrayNotHasKey($claim, $none);
        }
    }

    public function test_a_member_with_no_groups_gets_an_empty_groups_claim(): void
    {
        $tokens = $this->signIn(User::factory()->create(), $this->oidcClient(), ['scope' => 'openid groups']);

        $this->assertSame([], $this->jwtPayload($tokens['id_token'])['groups']);
    }

    public function test_there_is_no_id_token_without_the_openid_scope(): void
    {
        $tokens = $this->signIn(User::factory()->create(), $this->oidcClient(), ['scope' => 'profile email']);

        $this->assertArrayNotHasKey('id_token', $tokens);
    }

    public function test_a_client_not_allowed_openid_gets_invalid_scope(): void
    {
        $client = $this->makeClientAllowing(['full_name', 'email', 'vatsim_details', 'country', 'groups']);

        $response = $this->authorizeRequest(User::factory()->create(), $client, ['scope' => 'openid email']);

        $this->assertSame('invalid_scope', $this->redirectQuery($response)['error']);
    }

    public function test_a_refresh_returns_new_tokens_but_no_id_token(): void
    {
        $client = $this->oidcClient();
        $tokens = $this->signIn(User::factory()->create(), $client, ['scope' => 'openid email']);

        $refreshed = $this->refresh($client, $tokens['refresh_token'])->assertOk()->json();

        $this->assertArrayHasKey('access_token', $refreshed);
        $this->assertArrayNotHasKey('id_token', $refreshed);
    }

    public function test_the_access_token_lists_group_ids_when_groups_is_granted(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $group->members()->attach($user->id);
        $client = $this->oidcClient();

        $withGroups = $this->signIn($user, $client, ['scope' => 'email groups'])['access_token'];
        $withoutGroups = $this->signIn($user, $client, ['scope' => 'email'])['access_token'];

        $this->assertSame([$group->id], $this->jwtPayload($withGroups)['groups']);
        $this->assertArrayNotHasKey('groups', $this->jwtPayload($withoutGroups));
    }

    public function test_the_user_api_ignores_the_oidc_scopes(): void
    {
        $user = User::factory()->create();
        $client = $this->oidcClient();
        $client->policy->update(['single_session' => false]);

        $plain = $this->signIn($user, $client, ['scope' => 'email'])['access_token'];
        $oidc = $this->signIn($user, $client, ['scope' => 'openid profile email'])['access_token'];

        $this->assertSame($this->bearerJson('GET', '/api/user', $plain)->assertOk()->json(), $this->bearerJson('GET', '/api/user', $oidc)->assertOk()->json());
    }

    private function oidcClient(): Client
    {
        return $this->makeClientAllowing(self::OIDC_SCOPES);
    }

    /** The at_hash of an access token: the left half of its SHA-256, base64url-encoded. */
    private function atHash(string $accessToken): string
    {
        return rtrim(strtr(base64_encode(substr(hash('sha256', $accessToken, true), 0, 16)), '+/', '-_'), '=');
    }
}
