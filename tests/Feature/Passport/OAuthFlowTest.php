<?php

namespace Tests\Feature\Passport;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

/**
 * End-to-end regression for the OAuth2 authorization code grant under
 * Passport 13. Exercises the full provider flow a third-party service uses:
 * create client -> authorize (auto-approved via skipsAuthorization) ->
 * exchange code for a signed access token -> call the protected /api/user
 * resource endpoint with a real bearer token.
 *
 * This proves that, together, the legacy integer client schema, hashed
 * client secrets, token signing/validation and user resolution all work.
 */
class OAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorization_code_grant_issues_a_working_bearer_token(): void
    {
        $user = User::factory()->create();
        $redirectUri = 'https://client.test/callback';

        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
            'Regression Client',
            [$redirectUri],
            confidential: true,
        );
        $plainSecret = $client->plainSecret;

        // 1. Authorization request. skipsAuthorization() is true for this app's
        // clients, so Passport auto-approves and redirects with a code.
        $authorizeResponse = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
            'client_id' => $client->id,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'full_name',
        ]));

        $authorizeResponse->assertRedirect();
        parse_str(parse_url($authorizeResponse->headers->get('Location'), PHP_URL_QUERY) ?? '', $params);
        $this->assertArrayHasKey('code', $params, 'Authorization request did not return a code.');

        // 2. Exchange the authorization code for an access token.
        $tokenResponse = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'client_secret' => $plainSecret,
            'redirect_uri' => $redirectUri,
            'code' => $params['code'],
        ]);

        $tokenResponse->assertOk();
        $accessToken = $tokenResponse->json('access_token');
        $this->assertNotEmpty($accessToken, 'Token endpoint did not return an access token.');

        // 3. Use the bearer token against the protected resource endpoint.
        $this->withHeaders(['Authorization' => 'Bearer '.$accessToken])
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }
}
