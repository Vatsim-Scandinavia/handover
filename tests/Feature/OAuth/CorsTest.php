<?php

declare(strict_types=1);

namespace Tests\Feature\OAuth;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Tests\Concerns\RunsOAuthFlows;
use Tests\TestCase;

class CorsTest extends TestCase
{
    use RefreshDatabase, RunsOAuthFlows;

    private const string ORIGIN = 'https://vcas-ok.test';

    private const string OTHER_ORIGIN = 'https://other.test';

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->publicClientFrom(self::ORIGIN);
    }

    private function publicClientFrom(string $origin): Client
    {
        $client = $this->makeClient(confidential: false);
        $client->policy->update(['allowed_scopes' => ['openid', 'profile'], 'allowed_origins' => [$origin]]);

        return $client;
    }

    private function preflight(string $uri, string $origin, string $method = 'POST'): TestResponse
    {
        return $this->call('OPTIONS', $uri, server: $this->transformHeadersToServerVars([
            'Origin' => $origin,
            'Access-Control-Request-Method' => $method,
            'Access-Control-Request-Headers' => 'authorization, content-type',
        ]));
    }

    private function exchangeFrom(string $origin, Client $client, string $code): TestResponse
    {
        return $this->withHeaders(['Origin' => $origin])
            ->exchangeCode($client, $code, ['code_verifier' => self::CODE_VERIFIER]);
    }

    private function userinfoFrom(string $origin, string $accessToken): TestResponse
    {
        return $this->withHeaders(['Origin' => $origin])->bearerJson('GET', '/oauth/userinfo', $accessToken);
    }

    private function accessToken(): string
    {
        $code = $this->authorizeCode(User::factory()->create(), $this->client, self::PKCE + ['scope' => 'openid']);

        return $this->exchangeFrom(self::ORIGIN, $this->client, $code)->assertOk()->json('access_token');
    }

    private function assertNoCors(TestResponse $response): void
    {
        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_the_token_and_userinfo_endpoints_answer_a_preflight_from_an_allowed_origin(): void
    {
        $this->preflight('/oauth/token', self::ORIGIN)
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', self::ORIGIN)
            ->assertHeader('Access-Control-Allow-Methods', 'POST')
            ->assertHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type')
            ->assertHeaderMissing('Access-Control-Allow-Credentials');

        $this->preflight('/oauth/userinfo', self::ORIGIN, 'GET')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', self::ORIGIN)
            ->assertHeader('Access-Control-Allow-Methods', 'GET')
            ->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    public function test_a_preflight_from_an_unknown_origin_is_refused(): void
    {
        $this->assertNoCors($this->preflight('/oauth/token', self::OTHER_ORIGIN)->assertForbidden()->assertHeader('Vary', 'Origin'));
        $this->assertNoCors($this->preflight('/oauth/userinfo', self::OTHER_ORIGIN, 'GET')->assertForbidden());
    }

    public function test_a_revoked_clients_origins_are_not_allowed(): void
    {
        app(ClientRepository::class)->delete($this->client);

        $this->assertNoCors($this->preflight('/oauth/token', self::ORIGIN)->assertForbidden());
    }

    public function test_the_token_endpoint_allows_the_clients_own_origin(): void
    {
        $code = $this->authorizeCode(User::factory()->create(), $this->client, self::PKCE);

        $this->exchangeFrom(self::ORIGIN, $this->client, $code)
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', self::ORIGIN)
            ->assertHeader('Vary', 'Origin')
            ->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    public function test_the_token_endpoint_refuses_another_clients_origin_before_it_uses_the_code(): void
    {
        $this->publicClientFrom(self::OTHER_ORIGIN);
        $code = $this->authorizeCode(User::factory()->create(), $this->client, self::PKCE);

        $this->assertNoCors($this->exchangeFrom(self::OTHER_ORIGIN, $this->client, $code)->assertForbidden());

        $this->exchangeFrom(self::ORIGIN, $this->client, $code)->assertOk();
    }

    public function test_userinfo_allows_the_origin_of_the_tokens_client(): void
    {
        $this->userinfoFrom(self::ORIGIN, $this->accessToken())
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', self::ORIGIN)
            ->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    public function test_userinfo_refuses_another_clients_origin(): void
    {
        $this->publicClientFrom(self::OTHER_ORIGIN);

        $this->assertNoCors($this->userinfoFrom(self::OTHER_ORIGIN, $this->accessToken())->assertForbidden());
    }

    public function test_userinfo_lets_an_allowed_origin_read_an_invalid_token_error(): void
    {
        $this->userinfoFrom(self::ORIGIN, 'not-a-token')
            ->assertUnauthorized()
            ->assertHeader('Access-Control-Allow-Origin', self::ORIGIN)
            ->assertHeader('Access-Control-Expose-Headers', 'WWW-Authenticate');

        $this->assertNoCors($this->userinfoFrom('https://unknown.test', 'not-a-token')->assertUnauthorized());
    }

    public function test_a_confidential_client_without_an_origin_is_unaffected(): void
    {
        $client = $this->makeClient();

        $this->assertNoCors($this->exchangeCode($client, $this->authorizeCode(User::factory()->create(), $client))->assertOk());
    }

    public function test_a_confidential_client_whose_server_sends_an_origin_is_unaffected(): void
    {
        $client = $this->makeClientAllowing(['openid']);
        $user = User::factory()->create();

        $exchange = $this->withHeaders(['Origin' => self::OTHER_ORIGIN])
            ->exchangeCode($client, $this->authorizeCode($user, $client, ['scope' => 'openid']));
        $this->assertNoCors($exchange->assertOk());

        $this->assertNoCors($this->userinfoFrom(self::OTHER_ORIGIN, $exchange->json('access_token'))->assertOk());
    }

    public function test_the_authorize_endpoint_and_the_user_api_get_no_cors(): void
    {
        $this->assertNoCors($this->preflight('/oauth/authorize', self::ORIGIN, 'GET'));
        $this->assertNoCors($this->preflight('/api/user', self::ORIGIN, 'GET'));
        $this->assertNoCors($this->withHeaders(['Origin' => self::ORIGIN])->getJson('/api/user'));
    }
}
