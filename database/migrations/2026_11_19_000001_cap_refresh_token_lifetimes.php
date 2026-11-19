<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Refresh tokens used to last 100 years. See ADR 0001.
 *
 * Each live refresh token now expires 30 days after its access token does.
 * Old access tokens keep their one-month expiry, and Control Center refreshes
 * only once the access token has expired, so a flat cap could expire a
 * refresh token before it is ever used. A refresh token with no access token
 * row, or one whose access token never expires, gets now plus 30 days.
 * The migration never extends a token.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The refresh grant checks only the refresh token's own flag.
        DB::table('oauth_refresh_tokens')
            ->where('revoked', false)
            ->whereIn('access_token_id', DB::table('oauth_access_tokens')->where('revoked', true)->select('id'))
            ->update(['revoked' => true]);

        DB::table('oauth_refresh_tokens as r')
            ->leftJoin('oauth_access_tokens as a', 'a.id', '=', 'r.access_token_id')
            ->where('r.revoked', false)
            ->select('r.id', 'r.expires_at', 'a.expires_at as access_expires_at')
            ->chunkById(1000, function ($rows) {
                foreach ($rows as $row) {
                    $cap = $row->access_expires_at === null
                        ? now()->addDays(30)
                        : Carbon::parse($row->access_expires_at)->addDays(30);

                    if ($row->expires_at === null || Carbon::parse($row->expires_at)->gt($cap)) {
                        DB::table('oauth_refresh_tokens')->where('id', $row->id)->update(['expires_at' => $cap]);
                    }
                }
            }, 'r.id', 'id');
    }

    public function down(): void
    {
        // Irreversible: the old expiries were 100 years and are not worth restoring.
    }
};
