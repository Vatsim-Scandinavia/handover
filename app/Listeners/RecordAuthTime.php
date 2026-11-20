<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

/**
 * Records when the member signed in to Handover, for the auth_time claim of
 * the id_tokens issued during this Handover session.
 */
class RecordAuthTime
{
    public const SESSION_KEY = 'auth_time';

    public function handle(Login $event): void
    {
        session()->put(self::SESSION_KEY, now()->getTimestamp());
    }
}
