<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\User;
use App\Vatsim\SyncMember;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Description('Refresh members\' VATSIM data and tokens.')]
#[Signature('member:data')]
class UpdateMemberData extends Command
{
    public function handle(SyncMember $sync): void
    {
        User::query()->whereNotNull('refresh_token')->lazyById()->each(function (User $user) use ($sync): void {
            try {
                $sync($user);
            } catch (Throwable $exception) {
                // One member's failure must not stop the others.
                report($exception);
            }
        });
    }
}
