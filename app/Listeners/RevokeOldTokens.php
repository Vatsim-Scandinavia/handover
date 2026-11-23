<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Passport\ClientPolicy;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Events\AccessTokenCreated;

class RevokeOldTokens
{
    /**
     * Keep one live token set per user for a client whose policy asks for a
     * single session. See docs/adr/0001-token-lifetimes-and-refresh-policy.md.
     */
    public function handle(AccessTokenCreated $event): void
    {
        $policy = ClientPolicy::find($event->clientId);

        // Every client has a policy. Fail closed if one is missing.
        if ($policy !== null && ! $policy->single_session) {
            return;
        }

        $oldTokens = DB::table('oauth_access_tokens')
            ->where('id', '<>', $event->tokenId)
            ->where('user_id', $event->userId)
            ->where('client_id', $event->clientId);

        // The refresh grant checks only the refresh token's own flag, so revoke
        // the refresh tokens too. The one just issued points at the new token.
        DB::table('oauth_refresh_tokens')
            ->where('revoked', false)
            ->whereIn('access_token_id', (clone $oldTokens)->select('id'))
            ->update(['revoked' => true]);

        $oldTokens->update(['revoked' => true]);
    }
}
