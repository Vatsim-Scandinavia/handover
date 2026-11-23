<?php

declare(strict_types=1);

namespace Tests\Feature\OAuth;

use App\OAuth\AccessToken;
use App\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Bridge\AccessToken as PassportAccessToken;
use Laravel\Passport\Bridge\Client as ClientEntity;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Client;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use League\OAuth2\Server\CryptKey;
use Tests\Concerns\RunsOAuthFlows;
use Tests\TestCase;

class AccessTokenSigningTest extends TestCase
{
    use RefreshDatabase, RunsOAuthFlows;

    public function test_new_access_tokens_carry_the_kid_of_the_active_key(): void
    {
        $tokens = $this->signIn(User::factory()->create(), $this->makeClient());

        $header = $this->jwtHeader($tokens['access_token']);
        $this->assertSame('RS256', $header['alg']);
        $this->assertSame($this->signingKeys()->active()->kid, $header['kid']);
        $this->assertKidIsPublished($header['kid']);
    }

    public function test_access_tokens_carry_the_same_claims_as_passports_own(): void
    {
        $user = User::factory()->create();
        $client = new ClientEntity((string) $this->makeClient()->getKey(), 'Test Client', [$this->redirectUri]);
        $scopes = [new Scope('full_name'), new Scope('email')];
        $expiry = new DateTimeImmutable('+1 hour');

        $ours = new AccessToken((string) $user->getKey(), $scopes, $client);
        $passports = new PassportAccessToken((string) $user->getKey(), $scopes, $client);
        foreach ([$ours, $passports] as $token) {
            $token->setIdentifier('token-id');
            $token->setExpiryDateTime($expiry);
        }
        $passports->setPrivateKey(new CryptKey(self::testPrivateKey(), null, false));

        $this->assertEquals(
            Arr::except($this->jwtPayload($passports->toString()), ['iat', 'nbf']),
            Arr::except($this->jwtPayload($ours->toString()), ['iat', 'nbf']),
        );
        $this->assertSame(
            array_keys($this->jwtPayload($passports->toString())),
            array_keys($this->jwtPayload($ours->toString())),
        );
    }

    public function test_after_activation_new_tokens_use_the_new_key_and_old_tokens_still_validate(): void
    {
        $user = User::factory()->create();
        $old = $this->signIn($user, $this->makeClient())['access_token'];

        $new = $this->generateKey();
        $this->activateKey($new);
        $fresh = $this->signIn($user, $this->makeClient())['access_token'];

        $this->assertSame($new, $this->jwtHeader($fresh)['kid']);
        $this->callApi($fresh)->assertOk();
        $this->callApi($old)->assertOk();
    }

    public function test_a_token_signed_by_a_removed_key_is_refused(): void
    {
        $original = $this->signingKeys()->active()->kid;
        $token = $this->signIn(User::factory()->create(), $this->makeClient())['access_token'];

        $this->activateKey($this->generateKey());
        $this->removeKey($original);

        $this->callApi($token)->assertUnauthorized();
    }

    public function test_revoking_the_active_key_fails_its_tokens_at_once_and_a_refresh_recovers(): void
    {
        $original = $this->signingKeys()->active()->kid;
        $client = $this->makeClient();
        $tokens = $this->signIn(User::factory()->create(), $client);

        $this->artisan('signing-keys:revoke', ['kid' => $original])->assertSuccessful();

        $this->callApi($tokens['access_token'])->assertUnauthorized();
        $this->assertNotSame($original, $this->signingKeys()->active()->kid);
        $this->assertKidIsNotPublished($original);

        $fresh = $this->refresh($client, $tokens['refresh_token'])->assertOk()->json('access_token');
        $this->assertSame($this->signingKeys()->active()->kid, $this->jwtHeader($fresh)['kid']);
        $this->callApi($fresh)->assertOk();
    }

    public function test_a_token_with_an_unknown_kid_is_refused(): void
    {
        $user = User::factory()->create();
        $client = $this->makeClient();
        $foreignKey = self::newPrivateKey();

        $this->callApi($this->forgeToken($user, $client, $foreignKey, 'not-a-published-kid'))->assertUnauthorized();
    }

    public function test_a_token_with_a_published_kid_but_another_keys_signature_is_refused(): void
    {
        $user = User::factory()->create();
        $client = $this->makeClient();

        $token = $this->forgeToken($user, $client, self::newPrivateKey(), $this->signingKeys()->active()->kid);

        $this->callApi($token)->assertUnauthorized();
    }

    public function test_tokens_issued_before_the_upgrade_without_a_kid_validate_against_the_imported_key(): void
    {
        $this->emptySigningKeyStore();
        $legacyKey = $this->installLegacyKeyFiles();
        $this->artisan('signing-keys:import')->assertSuccessful();
        $user = User::factory()->create();
        $client = $this->makeClient();

        $legacyToken = $this->forgeToken($user, $client, $legacyKey, kid: null);

        $this->callApi($legacyToken)->assertOk();

        // It keeps validating after a rotation, while the imported key is retired but published.
        $imported = $this->signingKeys()->active()->kid;
        $this->activateKey($this->generateKey());
        $this->callApi($legacyToken)->assertOk();

        $this->removeKey($imported);
        $this->callApi($legacyToken)->assertUnauthorized();
    }

    public function test_a_token_without_a_kid_is_refused_when_no_key_was_imported(): void
    {
        $user = User::factory()->create();
        $client = $this->makeClient();
        $this->artisan('signing-keys:revoke', ['kid' => $this->signingKeys()->active()->kid])->assertSuccessful();

        $this->callApi($this->forgeToken($user, $client, self::testPrivateKey(), kid: null))->assertUnauthorized();
    }

    private function callApi(string $accessToken): TestResponse
    {
        return $this->bearerJson('GET', '/api/user', $accessToken);
    }

    /**
     * A token shaped like Passport's, with a live database row, signed by any key.
     * Passport 12 and earlier issued these with no kid.
     */
    private function forgeToken(User $user, Client $client, string $privateKeyPem, ?string $kid): string
    {
        $id = Str::random(80);
        $client->tokens()->forceCreate([
            'id' => $id, 'user_id' => $user->getKey(), 'client_id' => $client->getKey(),
            'scopes' => [], 'revoked' => false, 'expires_at' => now()->addHour(),
        ]);

        $builder = Builder::new(new JoseEncoder, ChainedFormatter::default());
        if ($kid !== null) {
            $builder = $builder->withHeader('kid', $kid);
        }

        return $builder
            ->permittedFor((string) $client->getKey())
            ->identifiedBy($id)
            ->issuedAt(new DateTimeImmutable)
            ->canOnlyBeUsedAfter(new DateTimeImmutable)
            ->expiresAt(new DateTimeImmutable('+1 hour'))
            ->relatedTo((string) $user->getKey())
            ->withClaim('scopes', [])
            ->getToken(new Sha256, InMemory::plainText($privateKeyPem))
            ->toString();
    }

    private function assertKidIsPublished(string $kid): void
    {
        $this->assertContains($kid, $this->jwksKids());
    }

    private function assertKidIsNotPublished(string $kid): void
    {
        $this->assertNotContains($kid, $this->jwksKids());
    }
}
