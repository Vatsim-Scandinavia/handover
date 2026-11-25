<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\Concerns\FakesVatsimConnect;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use FakesVatsimConnect;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['vatsim_auth.scopes' => ['full_name', 'email', 'vatsim_details', 'country']]);
    }

    public function test_login_redirects_to_vatsim_with_the_required_scopes(): void
    {
        $this->fakeVatsim();

        $location = $this->redirectLocation($this->get('/login'));
        $this->assertStringStartsWith('https://vatsim.test/oauth/authorize?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('full_name email vatsim_details country', $query['required_scopes']);
        $this->assertSame($query['state'], session('oauthstate'));
    }

    public function test_login_uses_the_configured_vatsim_server(): void
    {
        config(['vatsim_auth.base' => 'https://auth.vatsim.test', 'vatsim_auth.id' => 'handover']);

        $location = $this->redirectLocation($this->get('/login'));

        $this->assertStringStartsWith('https://auth.vatsim.test/oauth/authorize?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('handover', $query['client_id']);
        $this->assertSame(route('login'), $query['redirect_uri']);
    }

    public function test_a_callback_with_a_foreign_state_is_rejected(): void
    {
        $this->fakeVatsim();

        $this->withSession(['oauthstate' => 'expected'])
            ->get('/login?code=abc&state=forged')
            ->assertRedirect('/')
            ->assertSessionHas('error');

        $this->assertSame([], $this->vatsimRequests());
        $this->assertGuest();
    }

    public function test_a_returning_member_is_updated_and_logged_in(): void
    {
        $user = User::factory()->create(['first_name' => 'Old', 'rating' => 1]);
        $this->fakeVatsim($this->vatsimTokenResponse(), $this->vatsimUserResponse($user->id));

        $this->withSession(['oauthstate' => 'state'])
            ->get('/login?code=abc&state=state')
            ->assertRedirect(route('landing'));

        $this->assertAuthenticatedAs($user);
        parse_str((string) $this->vatsimRequests()[0]->getBody(), $tokenRequest);
        $this->assertSame(['authorization_code', 'abc'], [$tokenRequest['grant_type'], $tokenRequest['code']]);

        $user->refresh();
        $this->assertSame('Ola', $user->first_name);
        $this->assertSame(5, $user->rating);
        $this->assertSame('SCA', $user->subdivision);
        $this->assertSame('vatsim-access', $user->access_token);
        $this->assertSame('vatsim-refresh', $user->refresh_token);
        $this->assertEqualsWithDelta(time() + 3600, $user->token_expires, 5);
    }

    public function test_a_new_member_accepts_the_privacy_policy_before_being_stored(): void
    {
        $this->fakeVatsim($this->vatsimTokenResponse(), $this->vatsimUserResponse(1234567));

        $this->withSession(['oauthstate' => 'state'])
            ->get('/login?code=abc&state=state')
            ->assertRedirect(route('dpp'));

        $this->assertDatabaseMissing('users', ['id' => 1234567]);
        $this->assertGuest();

        $this->post(route('dpp.accept'))->assertRedirect(route('landing'));

        $user = User::query()->findOrFail(1234567);
        $this->assertAuthenticatedAs($user);
        $this->assertSame('vatsim-access', $user->access_token);
        $this->assertSame(1, (int) $user->accepted_privacy);
    }

    public function test_a_suspended_member_is_denied(): void
    {
        $this->fakeVatsim(
            $this->vatsimTokenResponse(),
            $this->vatsimUserResponse(1234567, ['rating' => ['id' => 0, 'short' => 'SUS', 'long' => 'Suspended']]),
        );

        $this->withSession(['oauthstate' => 'state'])
            ->get('/login?code=abc&state=state')
            ->assertRedirect(route('landing'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_accepting_the_privacy_policy_requires_a_vatsim_login(): void
    {
        $this->post(route('dpp.accept'))
            ->assertRedirect(route('landing'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_a_member_who_withheld_a_scope_is_denied(): void
    {
        $owner = $this->vatsimUser(1234567);
        Arr::forget($owner, 'data.personal.email');
        $this->fakeVatsim($this->vatsimTokenResponse(), $this->vatsimJson($owner));

        $this->withSession(['oauthstate' => 'state'])
            ->get('/login?code=abc&state=state')
            ->assertRedirect('/')
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_a_rejected_code_is_reported(): void
    {
        $this->fakeVatsim($this->vatsimJson(['error' => 'invalid_grant', 'message' => 'Code expired'], 400));

        $this->withSession(['oauthstate' => 'state'])
            ->get('/login?code=abc&state=state')
            ->assertRedirect('/')
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    /**
     * @param  TestResponse<Response>  $response
     */
    private function redirectLocation(TestResponse $response): string
    {
        $response->assertRedirect();

        return (string) $response->headers->get('Location');
    }
}
