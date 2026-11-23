<?php

declare(strict_types=1);

namespace App\Vatsim;

use App\User;

/**
 * Brings a member's stored VATSIM data up to date, refreshing their token
 * first if it has expired.
 */
final readonly class SyncMember
{
    public function __construct(private VatsimConnect $vatsim) {}

    public function __invoke(User $user): void
    {
        $token = $user->vatsim_token;
        if ($token === null) {
            return;
        }

        if ($token->getExpires() !== null && $token->hasExpired()) {
            $token = $this->vatsim->refresh($token);
            // Save now: VATSIM has spent the old refresh token, and a failed
            // fetch below must not lose the new one.
            $user->vatsim_token = $token;
            $user->save();
        }

        if ($token !== null) {
            $user->fillFromVatsim($this->vatsim->member($token))->save();
        }
    }
}
