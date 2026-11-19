<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Passport\Events\AccessTokenCreated;
use DB;

class RevokeOldTokens
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(AccessTokenCreated $event)
    {
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
