<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use App\OAuth\SigningKeys\SigningKey;
use App\OAuth\SigningKeys\SigningKeyStore;

#[Description('Delete a signing key at once, and replace it if it was active')]
#[Signature('signing-keys:revoke {kid : The kid of the key to drop}')]
class SigningKeysRevoke extends SigningKeyCommand
{
    protected function handleKeys(SigningKeyStore $keys): int
    {
        $replacement = $keys->revoke($this->argument('kid'));

        $this->info("Revoked key {$this->argument('kid')}. Tokens it signed no longer validate.");
        if ($replacement instanceof SigningKey) {
            $this->line("Activated new key {$replacement->kid}. Relying parties must refresh their tokens.");
        }

        return self::SUCCESS;
    }
}
