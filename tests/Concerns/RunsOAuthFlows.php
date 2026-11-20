<?php

namespace Tests\Concerns;

use App\User;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

/**
 * Drives the authorization code and refresh grants the way a relying party does.
 */
trait RunsOAuthFlows
{
    protected string $redirectUri = 'https://client.test/callback';

    protected function makeClient(bool $confidential = true): Client
    {
        return app(ClientRepository::class)->createAuthorizationCodeGrantClient(
            'Test Client',
            [$this->redirectUri],
            confidential: $confidential,
        );
    }

    protected function authorizeRequest(User $user, Client $client, array $params = []): TestResponse
    {
        return $this->actingAs($user)->get('/oauth/authorize?'.http_build_query($params + [
            'client_id' => $client->getKey(),
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
        ]));
    }

    /** Authorize and return the code from the redirect. */
    protected function authorizeCode(User $user, Client $client, array $params = []): string
    {
        $response = $this->authorizeRequest($user, $client, $params);
        $response->assertRedirect();
        $query = $this->redirectQuery($response);
        $this->assertArrayHasKey('code', $query, 'No code in redirect: '.$response->headers->get('Location'));

        return $query['code'];
    }

    protected function redirectQuery(TestResponse $response): array
    {
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY) ?? '', $query);

        return $query;
    }

    protected function exchangeCode(Client $client, string $code, array $params = []): TestResponse
    {
        return $this->post('/oauth/token', $params + [
            'grant_type' => 'authorization_code',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]);
    }

    protected function refresh(Client $client, string $refreshToken, array $params = []): TestResponse
    {
        return $this->post('/oauth/token', $params + [
            'grant_type' => 'refresh_token',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'refresh_token' => $refreshToken,
        ]);
    }

    /** Run the whole sign-in and return the token response JSON. */
    protected function signIn(User $user, Client $client, array $params = []): array
    {
        $response = $this->exchangeCode($client, $this->authorizeCode($user, $client, $params));
        $response->assertOk();

        return $response->json();
    }

    /** @return array<string, mixed> */
    protected function jwtPayload(string $jwt): array
    {
        return json_decode(base64_decode(strtr(explode('.', $jwt)[1], '-_', '+/')), true);
    }

    /** @return array<string, mixed> */
    protected function jwtHeader(string $jwt): array
    {
        return json_decode(base64_decode(strtr(explode('.', $jwt)[0], '-_', '+/')), true);
    }

    /** A JSON request with the access token as a bearer token, and no guard state left from earlier requests. */
    protected function bearerJson(string $method, string $uri, string $accessToken): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeaders(['Authorization' => 'Bearer '.$accessToken])->json($method, $uri);
    }
}
