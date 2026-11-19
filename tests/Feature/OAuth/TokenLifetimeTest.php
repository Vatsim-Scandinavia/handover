<?php

namespace Tests\Feature\OAuth;

use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\RunsOAuthFlows;
use Tests\TestCase;

class TokenLifetimeTest extends TestCase
{
    use RefreshDatabase, RunsOAuthFlows;

    public function test_access_tokens_last_one_hour(): void
    {
        $tokens = $this->signIn(User::factory()->create(), $this->makeClient());

        $this->assertEqualsWithDelta(3600, $tokens['expires_in'], 5);
    }

    public function test_a_new_sign_in_revokes_the_old_refresh_token(): void
    {
        $user = User::factory()->create();
        $client = $this->makeClient();

        $first = $this->signIn($user, $client);
        $this->signIn($user, $client);

        $this->refresh($client, $first['refresh_token'])->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');
    }

    public function test_a_sign_in_issues_a_refresh_token_valid_for_thirty_days(): void
    {
        $this->signIn(User::factory()->create(), $this->makeClient());

        $this->assertLiveRefreshTokenExpiresInThirtyDays();
    }

    public function test_a_refresh_issues_a_new_refresh_token_valid_for_thirty_days(): void
    {
        $user = User::factory()->create();
        $client = $this->makeClient();
        $first = $this->signIn($user, $client);
        DB::table('oauth_refresh_tokens')->update(['expires_at' => now()->addDay()]);

        $second = $this->refresh($client, $first['refresh_token'])->assertOk()->json();

        $this->assertNotSame($first['refresh_token'], $second['refresh_token']);
        $this->refresh($client, $first['refresh_token'])->assertStatus(400);
        // The window slides: the new token gets a full 30 days.
        $this->assertLiveRefreshTokenExpiresInThirtyDays();
    }

    public function test_the_refresh_token_just_issued_stays_valid(): void
    {
        $user = User::factory()->create();
        $client = $this->makeClient();
        $this->signIn($user, $client);

        $this->assertSame(1, DB::table('oauth_refresh_tokens')->where('revoked', false)->count());
    }

    // League checks expiry against the real clock, so $this->travel() cannot
    // exercise it. Check the stored expiry instead.
    private function assertLiveRefreshTokenExpiresInThirtyDays(): void
    {
        $expiresAt = DB::table('oauth_refresh_tokens')->where('revoked', false)->sole()->expires_at;

        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, Carbon::parse($expiresAt)->timestamp, 60);
    }
}
