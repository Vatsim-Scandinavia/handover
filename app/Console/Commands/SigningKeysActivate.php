<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use App\OAuth\SigningKeys\SigningKey;
use App\OAuth\SigningKeys\SigningKeyStore;

#[Description('Make a staged key sign new tokens, and retire the active key')]
#[Signature('signing-keys:activate
        {kid : The kid of a staged key}
        {--force : Activate the key even if it has been staged for less than an hour}')]
class SigningKeysActivate extends SigningKeyCommand
{
    protected function handleKeys(SigningKeyStore $keys): int
    {
        $retired = $keys->activate($this->argument('kid'), (bool) $this->option('force'));

        $this->info("Activated key {$this->argument('kid')}.");
        if ($retired instanceof SigningKey) {
            $this->line("Retired key {$retired->kid}. Remove it after {$retired->readyAt()->toIso8601String()} with: php artisan signing-keys:remove {$retired->kid}");
        }

        return self::SUCCESS;
    }
}
