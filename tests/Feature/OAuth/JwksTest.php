<?php

namespace Tests\Feature\OAuth;

use App\OAuth\SigningKeys\Jwk;
use Tests\TestCase;

class JwksTest extends TestCase
{
    public function test_it_publishes_the_active_key_as_an_rs256_signing_key(): void
    {
        $public = Jwk::fromPrivateKey(self::testPrivateKey());

        $this->getJson('/.well-known/jwks.json')
            ->assertOk()
            ->assertExactJson(['keys' => [[
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'kid' => Jwk::thumbprint($public),
                'n' => $public['n'],
                'e' => $public['e'],
            ]]]);
    }

    public function test_it_is_cacheable_for_an_hour_from_any_origin(): void
    {
        $response = $this->get('/.well-known/jwks.json', ['Origin' => 'https://rp.test']);

        $response->assertHeader('Access-Control-Allow-Origin', '*');
        $this->assertSame('max-age=3600, public', $response->headers->get('Cache-Control'));
        $response->assertCookieMissing(config('session.cookie'));
    }

    public function test_it_lists_staged_active_and_retired_keys_but_not_removed_ones(): void
    {
        $original = $this->signingKeys()->active()->kid;
        $first = $this->generateKey();
        $second = $this->generateKey();

        $this->assertEqualsCanonicalizing([$original, $first, $second], $this->jwksKids());

        $this->activateKey($first);
        $this->assertContains($original, $this->jwksKids());

        $this->removeKey($original);
        $this->assertNotContains($original, $this->jwksKids());
        $this->assertCount(2, $this->jwksKids());
    }
}
