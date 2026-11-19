<?php

namespace Tests\Feature\Passport;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

/**
 * Passport 13 throws its own AuthenticationException when a guest hits
 * /oauth/authorize. It carries no redirect target, so without one the
 * framework answers with an empty 401 and nobody can log in.
 */
class GuestAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $redirectUri = 'https://client.test/callback';

        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
            'Regression Client',
            [$redirectUri],
            confidential: true,
        );

        $this->get('/oauth/authorize?'.http_build_query([
            'client_id' => $client->id,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'full_name',
        ]))->assertRedirect(route('login'));
    }

    public function test_other_routes_keep_the_default_401(): void
    {
        // POST, since the landing page catches every GET.
        Route::post('/_test/unauthenticated', fn () => throw new AuthenticationException);

        $this->post('/_test/unauthenticated')->assertStatus(401);
    }
}
