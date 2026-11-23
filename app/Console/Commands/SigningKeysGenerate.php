<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\OAuth\SigningKeys\SigningKeyStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Description('Create a staged signing key')]
#[Signature('signing-keys:generate')]
class SigningKeysGenerate extends SigningKeyCommand
{
    protected function handleKeys(SigningKeyStore $keys): int
    {
        $key = $keys->generate();

        $this->info("Staged key {$key->kid}.");
        $this->line("Activate it after {$key->readyAt()->toIso8601String()} with: php artisan signing-keys:activate {$key->kid}");

        return self::SUCCESS;
    }
}
