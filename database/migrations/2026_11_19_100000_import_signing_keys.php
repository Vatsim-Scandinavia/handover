<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Moves Passport's signing key into the key store. See ADR 0003.
 *
 * The container entrypoint runs signing-keys:import on every start. This migration runs
 * it too, for installs that upgrade without the entrypoint. The command leaves a
 * store that already holds keys alone, so running it twice is safe.
 *
 * There is no down(): 3.x reads oauth-private.key, which the import removes.
 * Restore the storage volume backup to roll back.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Artisan::call('signing-keys:import') !== 0) {
            throw new RuntimeException('signing-keys:import failed: '.trim(Artisan::output()));
        }
    }
};
