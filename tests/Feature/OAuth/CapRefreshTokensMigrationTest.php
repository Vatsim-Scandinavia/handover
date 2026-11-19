<?php

namespace Tests\Feature\OAuth;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CapRefreshTokensMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeSecond();
    }

    public function test_caps_each_refresh_token_to_its_access_token_expiry_plus_thirty_days(): void
    {
        $this->accessToken('soon', now()->addDays(3));
        $this->accessToken('late', now()->addDays(28));
        $this->accessToken('expired', now()->subDays(5));
        $this->refreshToken('r-soon', 'soon');
        $this->refreshToken('r-late', 'late');
        $this->refreshToken('r-expired', 'expired');

        $this->migrate();

        $this->assertExpiry(now()->addDays(33), 'r-soon');
        $this->assertExpiry(now()->addDays(58), 'r-late');
        $this->assertExpiry(now()->addDays(25), 'r-expired');
        $this->assertSame(0, DB::table('oauth_refresh_tokens')->where('revoked', true)->count());
    }

    public function test_revokes_refresh_tokens_whose_access_token_is_revoked(): void
    {
        $this->accessToken('old', now()->addDays(10), revoked: true);
        $this->refreshToken('r-old', 'old');

        $this->migrate();

        $this->assertTrue((bool) DB::table('oauth_refresh_tokens')->find('r-old')->revoked);
    }

    public function test_caps_refresh_tokens_without_an_access_token_at_thirty_days_from_now(): void
    {
        $this->refreshToken('r-gone', 'gone');
        $this->accessToken('no-expiry', null);
        $this->refreshToken('r-no-expiry', 'no-expiry');

        $this->migrate();

        $this->assertExpiry(now()->addDays(30), 'r-gone');
        $this->assertExpiry(now()->addDays(30), 'r-no-expiry');
    }

    public function test_never_extends_a_refresh_token(): void
    {
        $this->accessToken('a', now()->addDays(20));
        $this->refreshToken('r-short', 'a', now()->addDays(2));

        $this->migrate();

        $this->assertExpiry(now()->addDays(2), 'r-short');
    }

    public function test_leaves_revoked_refresh_tokens_alone(): void
    {
        $this->accessToken('a', now()->addDays(20));
        $this->refreshToken('r-revoked', 'a', revoked: true);

        $this->migrate();

        $this->assertExpiry(now()->addYears(100), 'r-revoked');
    }

    private function migrate(): void
    {
        (require database_path('migrations/2026_11_19_000001_cap_refresh_token_lifetimes.php'))->up();
    }

    private function accessToken(string $id, ?Carbon $expiresAt, bool $revoked = false): void
    {
        DB::table('oauth_access_tokens')->insert([
            'id' => $id,
            'user_id' => 1,
            'client_id' => 1,
            'scopes' => '[]',
            'revoked' => $revoked,
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => $expiresAt,
        ]);
    }

    private function refreshToken(string $id, string $accessTokenId, ?Carbon $expiresAt = null, bool $revoked = false): void
    {
        DB::table('oauth_refresh_tokens')->insert([
            'id' => $id,
            'access_token_id' => $accessTokenId,
            'revoked' => $revoked,
            'expires_at' => $expiresAt ?? now()->addYears(100),
        ]);
    }

    private function assertExpiry(Carbon $expected, string $refreshTokenId): void
    {
        $actual = DB::table('oauth_refresh_tokens')->find($refreshTokenId)->expires_at;

        $this->assertSame($expected->toDateTimeString(), Carbon::parse($actual)->toDateTimeString(), $refreshTokenId);
    }
}
