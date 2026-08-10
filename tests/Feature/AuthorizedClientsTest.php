<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthorizedClientsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Insert an OAuth client using the application's legacy Passport schema.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createClient(array $overrides = []): int
    {
        return DB::table('oauth_clients')->insertGetId(array_merge([
            'user_id' => 1, // non-null owner => third-party (not first-party)
            'name' => 'vACC Norway',
            'secret' => Str::random(40),
            'redirect' => 'https://vacc-norway.test/callback',
            'personal_access_client' => false,
            'password_client' => false,
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    /**
     * Insert an access token linking a user to a client.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createToken(User $user, int $clientId, array $overrides = []): void
    {
        DB::table('oauth_access_tokens')->insert(array_merge([
            'id' => Str::random(80),
            'user_id' => $user->id,
            'client_id' => $clientId,
            'scopes' => null,
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->addDay(),
        ], $overrides));
    }

    public function test_lists_authorized_third_party_clients(): void
    {
        $user = User::factory()->create();
        $clientId = $this->createClient([
            'name' => 'vACC Norway',
            'redirect' => 'https://vacc-norway.test/callback',
        ]);
        $this->createToken($user, $clientId);

        $this->actingAs($user)
            ->getJson('/account/authorized-clients')
            ->assertOk()
            ->assertExactJson([
                ['name' => 'vACC Norway', 'url' => 'https://vacc-norway.test/callback'],
            ]);
    }

    public function test_returns_empty_array_when_user_has_no_authorized_clients(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/account/authorized-clients')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_excludes_revoked_expired_and_first_party_tokens(): void
    {
        $user = User::factory()->create();

        $thirdParty = $this->createClient(['redirect' => 'https://third-party.test/callback']);
        $this->createToken($user, $thirdParty, ['revoked' => true]);
        $this->createToken($user, $thirdParty, ['expires_at' => now()->subDay()]);

        // First-party clients (no owner) must never appear.
        $firstParty = $this->createClient(['user_id' => null]);
        $this->createToken($user, $firstParty);

        $this->actingAs($user)
            ->getJson('/account/authorized-clients')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/account/authorized-clients')
            ->assertUnauthorized();
    }
}
