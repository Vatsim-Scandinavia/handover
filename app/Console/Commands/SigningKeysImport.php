<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\OAuth\SigningKeys\SigningKeyStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Facades\File;
use Laravel\Passport\Passport;

/**
 * Fills an empty key store: it adopts Passport's original key, or creates the first
 * key on a fresh install. Safe to run on every start: a store that holds keys is
 * left alone. The container entrypoint and a migration both run it.
 */
#[Description('Adopt the Passport signing key as the first active key, or create one on a fresh install')]
#[Signature('signing-keys:import')]
class SigningKeysImport extends SigningKeyCommand
{
    protected function handleKeys(SigningKeyStore $keys): int
    {
        $fromEnvironment = config('passport.private_key');

        if (! $keys->isEmpty()) {
            $this->info('The signing key store already holds keys. Nothing to import.');
        } elseif (filled($fromEnvironment)) {
            $key = $keys->import(str_replace('\\n', "\n", $fromEnvironment));
            $this->info("Imported PASSPORT_PRIVATE_KEY as the active key {$key->kid}.");
        } elseif (is_file($legacyPath = Passport::keyPath('oauth-private.key'))) {
            $key = $keys->import((string) file_get_contents($legacyPath));
            $this->info("Imported {$legacyPath} as the active key {$key->kid}.");
            $this->removeLegacyKeyFiles();
        } else {
            $key = $keys->createFirst();
            $this->info("Created and activated the signing key {$key->kid}.");
        }

        if (filled($fromEnvironment)) {
            $this->warn("PASSPORT_PRIVATE_KEY is ignored. Handover signs with the keys in {$keys->directory()}. Remove the variable.");
        }

        return self::SUCCESS;
    }

    /** The store now owns the key. Leaving a copy would suggest it still signs. */
    private function removeLegacyKeyFiles(): void
    {
        foreach (['oauth-private.key', 'oauth-public.key'] as $file) {
            $path = Passport::keyPath($file);

            if (is_file($path) && ! File::delete($path)) {
                $this->warn("Could not delete {$path}. Delete it by hand: Handover no longer reads it.");
            }
        }
    }
}
