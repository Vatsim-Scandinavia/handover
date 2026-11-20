<?php

namespace Tests\Feature\OAuth;

use App\Models\Group;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Laravel\Passport\Client;
use Tests\Concerns\RunsOAuthFlows;
use Tests\TestCase;

class OidcUserinfoTest extends TestCase
{
    use RefreshDatabase, RunsOAuthFlows;

    public function test_get_and_post_return_the_same_claims_as_the_id_token(): void
    {
        $user = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        Group::factory()->create()->members()->attach($user->id);
        $tokens = $this->signIn($user, $this->oidcClient(), ['scope' => 'openid profile email groups']);

        $idTokenClaims = Arr::except(
            $this->jwtPayload($tokens['id_token']),
            ['iss', 'aud', 'exp', 'iat', 'auth_time', 'nonce', 'at_hash'],
        );

        $this->assertSame((string) $user->id, $idTokenClaims['sub']);
        $this->assertEquals($idTokenClaims, $this->bearerJson('GET', '/oauth/userinfo', $tokens['access_token'])->assertOk()->json());
        $this->assertEquals($idTokenClaims, $this->bearerJson('POST', '/oauth/userinfo', $tokens['access_token'])->assertOk()->json());
    }

    public function test_it_returns_only_sub_for_the_openid_scope_alone(): void
    {
        $user = User::factory()->create();
        $tokens = $this->signIn($user, $this->oidcClient(), ['scope' => 'openid']);

        $this->bearerJson('GET', '/oauth/userinfo', $tokens['access_token'])->assertOk()->assertExactJson(['sub' => (string) $user->id]);
    }

    public function test_a_token_without_the_openid_scope_is_refused(): void
    {
        $tokens = $this->signIn(User::factory()->create(), $this->oidcClient(), ['scope' => 'profile email']);

        $this->bearerJson('GET', '/oauth/userinfo', $tokens['access_token'])
            ->assertForbidden()
            ->assertHeader('WWW-Authenticate', 'Bearer error="insufficient_scope", scope="openid"');
    }

    public function test_a_request_without_a_token_is_refused(): void
    {
        $this->getJson('/oauth/userinfo')->assertUnauthorized();
    }

    private function oidcClient(): Client
    {
        return $this->makeClientAllowing(['openid', 'profile', 'email', 'groups']);
    }
}
