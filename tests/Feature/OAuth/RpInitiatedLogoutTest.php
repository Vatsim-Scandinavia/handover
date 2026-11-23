<?php

namespace Tests\Feature\OAuth;

use App\OAuth\Oidc\Issuer;
use App\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use Tests\Concerns\RunsOAuthFlows;
use Tests\TestCase;

class RpInitiatedLogoutTest extends TestCase
{
    use RefreshDatabase, RunsOAuthFlows;

    private const string LOGGED_OUT_URI = 'https://client.test/logged-out';

    private User $user;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->client = $this->makeClientAllowing(['openid']);
        $this->client->policy->update(['post_logout_redirect_uris' => [self::LOGGED_OUT_URI]]);
    }

    public function test_a_hint_for_the_signed_in_member_logs_out_silently_and_returns_to_the_client(): void
    {
        $idToken = $this->signIn($this->user, $this->client, ['scope' => 'openid'])['id_token'];
        session()->put('url.intended', '/somewhere');

        $this->logout([
            'id_token_hint' => $idToken,
            'post_logout_redirect_uri' => self::LOGGED_OUT_URI,
            'state' => 'af0ifjsldkj',
        ])->assertRedirect(self::LOGGED_OUT_URI.'?state=af0ifjsldkj');

        $this->assertGuest();
        $this->assertFalse(session()->has('url.intended'));
    }

    public function test_without_a_redirect_uri_the_member_sees_that_they_are_logged_out(): void
    {
        $this->actingAs($this->user);

        $this->logout(['id_token_hint' => $this->hint()])
            ->assertOk()
            ->assertSee("You're logged out", false);

        $this->assertGuest();
    }

    public function test_the_client_may_post_the_logout_request(): void
    {
        $this->actingAs($this->user);
        $params = ['id_token_hint' => $this->hint(), 'post_logout_redirect_uri' => self::LOGGED_OUT_URI];

        // A cross-site POST carries no Lax session cookie, so Handover reads
        // the session only after the browser follows the redirect to a GET.
        $this->post('/oauth/logout', $params + ['ui_locales' => 'en'])
            ->assertStatus(303)
            ->assertRedirect(route('oidc.logout', $params))
            ->assertCookieMissing(config('session.cookie'));
        $this->assertAuthenticatedAs($this->user);

        $this->logout($params)->assertRedirect(self::LOGGED_OUT_URI);
        $this->assertGuest();
    }

    public function test_an_expired_hint_still_counts(): void
    {
        $this->actingAs($this->user);

        $this->logout([
            'id_token_hint' => $this->hint(['exp' => new DateTimeImmutable('-1 day')]),
            'post_logout_redirect_uri' => self::LOGGED_OUT_URI,
        ])->assertRedirect(self::LOGGED_OUT_URI);

        $this->assertGuest();
    }

    public function test_an_unverifiable_hint_or_one_for_another_member_asks_for_confirmation(): void
    {
        $this->actingAs($this->user);

        foreach ([
            'unknown key' => [
                'id_token_hint' => $this->hint(privateKey: self::newPrivateKey()),
                'client_id' => $this->client->getKey(),
                'post_logout_redirect_uri' => self::LOGGED_OUT_URI,
            ],
            'another issuer' => ['id_token_hint' => $this->hint(['iss' => 'https://other.test'])],
            'not a JWT' => ['id_token_hint' => 'not-a-jwt'],
            'another member' => ['id_token_hint' => $this->hint(['sub' => (string) User::factory()->create()->id])],
            'several audiences' => ['id_token_hint' => $this->hint(['aud' => [(string) $this->client->getKey(), 'other']])],
        ] as $case => $params) {
            $response = $this->logout($params);
            $this->assertSame(200, $response->status(), $case);
            $response->assertSee('Log out of Handover?');
        }

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_without_a_hint_the_member_confirms_with_a_form_that_carries_the_request(): void
    {
        $this->actingAs($this->user);

        $response = $this->logout([
            'client_id' => $this->client->getKey(),
            'post_logout_redirect_uri' => self::LOGGED_OUT_URI,
            'state' => 'xyz',
        ])->assertOk()->assertSee('Log out of Handover?');

        $response->assertSee('action="'.route('oidc.logout').'"', false);
        $response->assertSee('name="_token" value="'.session()->token().'"', false);
        $response->assertSee('name="client_id" value="'.$this->client->getKey().'"', false);
        $response->assertSee('name="post_logout_redirect_uri" value="'.self::LOGGED_OUT_URI.'"', false);
        $response->assertSee('name="state" value="xyz"', false);
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_confirming_with_the_csrf_token_logs_out_and_returns_to_the_client(): void
    {
        $this->actingAs($this->user)->withSession(['_token' => 'csrf-token']);

        $this->postFromHandover([
            '_token' => 'csrf-token',
            'client_id' => $this->client->getKey(),
            'post_logout_redirect_uri' => self::LOGGED_OUT_URI,
            'state' => 'xyz',
        ])->assertRedirect(self::LOGGED_OUT_URI.'?state=xyz');

        $this->assertGuest();
    }

    public function test_a_post_without_a_valid_csrf_token_does_not_log_out(): void
    {
        $this->actingAs($this->user)->withSession(['_token' => 'csrf-token']);

        $this->postFromHandover(['_token' => 'wrong'])
            ->assertOk()
            ->assertSee('Log out of Handover?');
        $this->post('/oauth/logout')
            ->assertRedirect(route('oidc.logout'));

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_a_cross_site_post_with_a_token_but_no_session_cookie_becomes_a_get(): void
    {
        $this->actingAs($this->user)->withSession(['_token' => 'csrf-token']);

        $this->post('/oauth/logout', ['_token' => 'csrf-token'])
            ->assertStatus(303)
            ->assertRedirect(route('oidc.logout'))
            ->assertCookieMissing(config('session.cookie'));

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_logout_invalidates_the_session_and_regenerates_the_csrf_token(): void
    {
        $this->actingAs($this->user)->withSession(['_token' => 'csrf-token', 'url.intended' => '/somewhere']);

        $this->postFromHandover(['_token' => 'csrf-token'])
            ->assertOk()
            ->assertSee("You're logged out", false);

        $this->assertGuest();
        $this->assertNotSame('csrf-token', session()->token());
        $this->assertFalse(session()->has('url.intended'));
    }

    public function test_logout_revokes_no_tokens(): void
    {
        $tokens = $this->signIn($this->user, $this->client, ['scope' => 'openid']);

        $this->logout(['id_token_hint' => $tokens['id_token']])->assertOk();

        $this->assertSame(0, DB::table('oauth_access_tokens')->where('revoked', true)->count());
        $this->assertSame(0, DB::table('oauth_refresh_tokens')->where('revoked', true)->count());
    }

    public function test_a_hint_and_client_id_that_disagree_show_an_error(): void
    {
        $other = $this->makeClient();
        $this->actingAs($this->user);

        $this->logout([
            'id_token_hint' => $this->hint(),
            'client_id' => $other->getKey(),
            'post_logout_redirect_uri' => self::LOGGED_OUT_URI,
        ])->assertStatus(400)->assertSee('The logout request names two different services.');

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_an_unregistered_redirect_uri_shows_an_error(): void
    {
        $revoked = $this->makeClient();
        $revoked->policy->update(['post_logout_redirect_uris' => [self::LOGGED_OUT_URI]]);
        $revoked->forceFill(['revoked' => true])->save();
        $this->actingAs($this->user);

        foreach ([
            'not registered' => ['id_token_hint' => $this->hint(), 'post_logout_redirect_uri' => self::LOGGED_OUT_URI.'/'],
            'no client' => ['post_logout_redirect_uri' => self::LOGGED_OUT_URI],
            'unknown client' => ['client_id' => 'no-such-client', 'post_logout_redirect_uri' => self::LOGGED_OUT_URI],
            'revoked client' => ['client_id' => $revoked->getKey(), 'post_logout_redirect_uri' => self::LOGGED_OUT_URI],
            'a redirect URI' => ['client_id' => $this->client->getKey(), 'post_logout_redirect_uri' => $this->redirectUri],
        ] as $case => $params) {
            $response = $this->logout($params);
            $this->assertSame(400, $response->status(), $case);
            $response->assertSee("can't send you back");
        }

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_when_nobody_is_signed_in_it_returns_straight_to_the_client(): void
    {
        $this->logout([
            'client_id' => $this->client->getKey(),
            'post_logout_redirect_uri' => self::LOGGED_OUT_URI,
            'state' => 'xyz',
        ])->assertRedirect(self::LOGGED_OUT_URI.'?state=xyz');

        $this->logout(['id_token_hint' => $this->hint(), 'post_logout_redirect_uri' => self::LOGGED_OUT_URI])
            ->assertRedirect(self::LOGGED_OUT_URI);
        $this->logout()->assertOk()->assertSee("You're logged out", false);
        $this->get('/logout')->assertOk()->assertSee("You're logged out", false);
    }

    public function test_the_state_is_added_to_a_redirect_uri_that_has_a_query(): void
    {
        $this->client->policy->update(['post_logout_redirect_uris' => ['https://client.test/?page=bye']]);

        $this->logout([
            'client_id' => $this->client->getKey(),
            'post_logout_redirect_uri' => 'https://client.test/?page=bye',
            'state' => 'a b',
        ])->assertRedirect('https://client.test/?page=bye&state=a+b');
    }

    public function test_the_old_logout_path_asks_for_confirmation(): void
    {
        $this->actingAs($this->user)
            ->get('/logout')
            ->assertOk()
            ->assertSee('Log out of Handover?')
            ->assertSee('action="'.route('oidc.logout').'"', false);

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_the_dashboard_logs_out_with_a_post_form(): void
    {
        $this->actingAs($this->user)
            ->get('/')
            ->assertOk()
            ->assertSee('action="'.route('oidc.logout').'"', false)
            ->assertSee('name="_token"', false);
    }

    /**
     * A POST from Handover's own form, which is same-site and so carries the session cookie.
     *
     * @param  array<string, string>  $data
     */
    private function postFromHandover(array $data): TestResponse
    {
        return $this->withCookie(config('session.cookie'), 'session')->post('/oauth/logout', $data);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function logout(array $params = []): TestResponse
    {
        return $this->get('/oauth/logout?'.http_build_query($params));
    }

    /**
     * An id_token for the member and the client, signed by the active key
     * unless another private key is given.
     *
     * @param  array{iss?: string, sub?: string, aud?: list<string>, exp?: DateTimeImmutable}  $claims
     */
    private function hint(array $claims = [], ?string $privateKey = null): string
    {
        $key = $this->signingKeys()->active();

        return Builder::new(new JoseEncoder(), ChainedFormatter::default())
            ->withHeader('kid', $key->kid)
            ->issuedBy($claims['iss'] ?? Issuer::url())
            ->permittedFor(...$claims['aud'] ?? [(string) $this->client->getKey()])
            ->relatedTo($claims['sub'] ?? (string) $this->user->id)
            ->issuedAt(new DateTimeImmutable('-2 days'))
            ->expiresAt($claims['exp'] ?? new DateTimeImmutable('+1 hour'))
            ->getToken(new Sha256(), InMemory::plainText($privateKey ?? $key->privateKeyPem))
            ->toString();
    }
}
