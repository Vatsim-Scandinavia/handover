<?php

namespace App\Console\Commands;

use App\OAuth\SigningKeys\SigningKeyStore;

class SigningKeysRemove extends SigningKeyCommand
{
    protected $signature = 'signing-keys:remove
        {kid : The kid of a retired key}
        {--force : Remove the key even if it has been retired for less than two hours}';
    protected $description = 'Delete a retired signing key and stop publishing it';

    protected function handleKeys(SigningKeyStore $keys): int
    {
        $keys->remove($this->argument('kid'), (bool) $this->option('force'));

        $this->info("Removed key {$this->argument('kid')}.");

        return self::SUCCESS;
    }
}
