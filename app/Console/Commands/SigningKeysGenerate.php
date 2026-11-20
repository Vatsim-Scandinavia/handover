<?php

namespace App\Console\Commands;

use App\OAuth\SigningKeys\SigningKeyStore;

class SigningKeysGenerate extends SigningKeyCommand
{
    protected $signature = 'signing-keys:generate';
    protected $description = 'Create a staged signing key';

    protected function handleKeys(SigningKeyStore $keys): int
    {
        $key = $keys->generate();

        $this->info("Staged key {$key->kid}.");
        $this->line("Activate it after {$key->readyAt()->toIso8601String()} with: php artisan signing-keys:activate {$key->kid}");

        return self::SUCCESS;
    }
}
