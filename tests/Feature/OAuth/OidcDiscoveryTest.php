<?php

namespace Tests\Feature\OAuth;

use Illuminate\Support\Arr;
use Tests\TestCase;

class OidcDiscoveryTest extends TestCase
{
    public function test_it_describes_the_provider(): void
    {
        $issuer = rtrim(config('app.url'), '/');

        $metadata = $this->getJson('/.well-known/openid-configuration')->assertOk()->json();

        // Dev-only packages may register more scopes, such as laravel/mcp's mcp:use.
        $this->assertSame(
            [],
            array_diff(['full_name', 'email', 'vatsim_details', 'country', 'groups', 'openid', 'profile'], $metadata['scopes_supported']),
        );
        $this->assertEquals([
                'issuer' => $issuer,
                'authorization_endpoint' => $issuer.'/oauth/authorize',
                'token_endpoint' => $issuer.'/oauth/token',
                'userinfo_endpoint' => $issuer.'/oauth/userinfo',
                'jwks_uri' => $issuer.'/.well-known/jwks.json',
                'end_session_endpoint' => $issuer.'/oauth/logout',
                'claims_supported' => [
                    'iss', 'sub', 'aud', 'exp', 'iat', 'auth_time', 'nonce', 'at_hash',
                    'name', 'given_name', 'family_name', 'email', 'groups',
                ],
                'response_types_supported' => ['code'],
                'subject_types_supported' => ['public'],
                'id_token_signing_alg_values_supported' => ['RS256'],
                'code_challenge_methods_supported' => ['S256'],
            ], Arr::except($metadata, 'scopes_supported'));
    }

    public function test_it_is_readable_from_any_origin_and_sets_no_cookie(): void
    {
        $response = $this->get('/.well-known/openid-configuration', ['Origin' => 'https://rp.test']);

        $response->assertHeader('Access-Control-Allow-Origin', '*');
        $response->assertCookieMissing(config('session.cookie'));
    }
}
