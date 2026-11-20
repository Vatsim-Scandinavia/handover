<?php

namespace App\Console\Commands;

use App\OAuth\SigningKeys\SigningKeyStore;

class SigningKeysRevoke extends SigningKeyCommand
{
    protected $signature = 'signing-keys:revoke {kid : The kid of the key to drop}';
    protected $description = 'Delete a signing key at once, and replace it if it was active';

    protected function handleKeys(SigningKeyStore $keys): int
    {
        $replacement = $keys->revoke($this->argument('kid'));

        $this->info("Revoked key {$this->argument('kid')}. Tokens it signed no longer validate.");
        if ($replacement !== null) {
            $this->line("Activated new key {$replacement->kid}. Relying parties must refresh their tokens.");
        }

        return self::SUCCESS;
    }
}
