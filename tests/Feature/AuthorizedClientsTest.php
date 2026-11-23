<?php

namespace Tests\Feature;

use App\Services\AuthorizedClients;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Tests\TestCase;

class AuthorizedClientsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create an OAuth client and return its id. Like real clients, it has no
     * owner, which Passport 13 counts as "first party".
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createClient(array $overrides = []): string
    {
        return Client::factory()->create([
            'name' => 'vACC Norway',
            'redirect_uris' => ['https://vacc-norway.test/callback'],
            ...$overrides,
        ])->id;
    }

    /**
     * Insert an access token linking a user to a client.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createToken(User $user, string $clientId, array $overrides = []): string
    {
        $id = Str::random(80);

        DB::table('oauth_access_tokens')->insert(array_merge([
            'id' => $id,
            'user_id' => $user->id,
            'client_id' => $clientId,
            'scopes' => null,
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->addDay(),
        ], $overrides));

        return $id;
    }

    /**
     * Insert a refresh token for an access token.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createRefreshToken(string $accessTokenId, array $overrides = []): void
    {
        DB::table('oauth_refresh_tokens')->insert(array_merge([
            'id' => Str::random(80),
            'access_token_id' => $accessTokenId,
            'revoked' => false,
            'expires_at' => now()->addDays(30),
        ], $overrides));
    }

    /**
     * @return list<array{name: string, url: ?string}>
     */
    private function authorizedClients(User $user): array
    {
        return app(AuthorizedClients::class)->for($user)->all();
    }

    public function test_lists_authorized_third_party_clients(): void
    {
        $user = User::factory()->create();
        $clientId = $this->createClient([
            'name' => 'vACC Norway',
            'redirect_uris' => ['https://vacc-norway.test/callback'],
        ]);
        $this->createToken($user, $clientId);

        $this->assertSame([
                ['name' => 'vACC Norway', 'url' => 'https://vacc-norway.test'],
            ], $this->authorizedClients($user));
    }

    public function test_leaves_out_the_url_when_the_redirect_uri_is_not_a_web_address(): void
    {
        $user = User::factory()->create();
        $this->createToken($user, $this->createClient(['redirect_uris' => ['com.example.app:/callback']]));

        $this->assertSame([['name' => 'vACC Norway', 'url' => null]], $this->authorizedClients($user));
    }

    public function test_dashboard_lists_authorized_clients(): void
    {
        $user = User::factory()->create();
        $this->createToken($user, $this->createClient(['name' => 'vACC Norway']));

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('vACC Norway');
    }

    public function test_lists_nothing_when_user_has_no_authorized_clients(): void
    {
        $user = User::factory()->create();

        $this->assertSame([], $this->authorizedClients($user));
    }

    public function test_excludes_revoked_and_expired_tokens(): void
    {
        $user = User::factory()->create();

        $clientId = $this->createClient();
        $this->createToken($user, $clientId, ['revoked' => true]);
        $this->createToken($user, $clientId, ['expires_at' => now()->subDay()]);

        $this->assertSame([], $this->authorizedClients($user));
    }

    public function test_excludes_personal_access_and_password_clients(): void
    {
        $user = User::factory()->create();

        $personal = Client::factory()->asPersonalAccessTokenClient()->create()->id;
        $password = Client::factory()->asPasswordClient()->create()->id;
        $this->createToken($user, $personal);
        $this->createToken($user, $password);

        $this->assertSame([], $this->authorizedClients($user));
    }

    public function test_lists_client_with_expired_access_token_but_valid_refresh_token(): void
    {
        $user = User::factory()->create();
        $clientId = $this->createClient();
        $tokenId = $this->createToken($user, $clientId, ['expires_at' => now()->subHour()]);
        $this->createRefreshToken($tokenId);

        $this->assertSame([
                ['name' => 'vACC Norway', 'url' => 'https://vacc-norway.test'],
            ], $this->authorizedClients($user));
    }

    public function test_excludes_client_whose_refresh_token_is_revoked_or_expired(): void
    {
        $user = User::factory()->create();
        $clientId = $this->createClient();

        $revoked = $this->createToken($user, $clientId, ['expires_at' => now()->subHour()]);
        $this->createRefreshToken($revoked, ['revoked' => true]);
        $expired = $this->createToken($user, $clientId, ['expires_at' => now()->subHour()]);
        $this->createRefreshToken($expired, ['expires_at' => now()->subDay()]);

        $this->assertSame([], $this->authorizedClients($user));
    }
}
