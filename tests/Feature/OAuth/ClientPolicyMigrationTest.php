<?php

declare(strict_types=1);

namespace Tests\Feature\OAuth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientPolicyMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const string MIGRATION = '2026_11_19_000003_create_oauth_client_policies.php';

    public function test_backfills_a_policy_that_keeps_todays_behaviour_for_every_client(): void
    {
        $migration = require database_path('migrations/'.self::MIGRATION);
        $migration->down();
        foreach (['5', '6'] as $id) {
            DB::table('oauth_clients')->insert([
                'id' => $id, 'name' => "Client $id", 'secret' => 'hashed', 'revoked' => $id === '6',
                'redirect_uris' => '["https://client.test/callback"]', 'grant_types' => '["authorization_code"]',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $migration->up();

        $policies = DB::table('oauth_client_policies')->orderBy('client_id')->get();
        $this->assertSame(['5', '6'], $policies->pluck('client_id')->all());
        foreach ($policies as $policy) {
            $this->assertSame(['full_name', 'email', 'vatsim_details', 'country', 'groups'], json_decode($policy->allowed_scopes));
            $this->assertEquals(1, $policy->single_session);
        }
        $this->assertSame(0, DB::table('oauth_client_required_groups')->count());
    }

    public function test_every_existing_client_starts_with_no_allowed_origins(): void
    {
        $migration = require database_path('migrations/2026_11_19_300000_add_allowed_origins_to_oauth_client_policies.php');
        $migration->down();
        DB::table('oauth_clients')->insert([
            'id' => '5', 'name' => 'Client 5', 'secret' => 'hashed', 'revoked' => false,
            'redirect_uris' => '["https://client.test/callback"]', 'grant_types' => '["authorization_code"]',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('oauth_client_policies')->insert(['client_id' => '5', 'allowed_scopes' => '["email"]']);

        $migration->up();

        $this->assertSame('[]', DB::table('oauth_client_policies')->where('client_id', '5')->value('allowed_origins'));
    }

    public function test_every_existing_client_starts_with_no_post_logout_redirect_uris(): void
    {
        $migration = require database_path('migrations/2026_11_19_400000_add_post_logout_redirect_uris_to_oauth_client_policies.php');
        $migration->down();
        DB::table('oauth_clients')->insert([
            'id' => '5', 'name' => 'Client 5', 'secret' => 'hashed', 'revoked' => false,
            'redirect_uris' => '["https://client.test/callback"]', 'grant_types' => '["authorization_code"]',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('oauth_client_policies')->insert(['client_id' => '5', 'allowed_scopes' => '["email"]']);

        $migration->up();

        $this->assertSame('[]', DB::table('oauth_client_policies')->where('client_id', '5')->value('post_logout_redirect_uris'));
    }
}
