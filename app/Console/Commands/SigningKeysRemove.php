<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\OAuth\SigningKeys\SigningKeyStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Description('Delete a retired signing key and stop publishing it')]
#[Signature('signing-keys:remove
        {kid : The kid of a retired key}
        {--force : Remove the key even if it has been retired for less than two hours}')]
class SigningKeysRemove extends SigningKeyCommand
{
    protected function handleKeys(SigningKeyStore $keys): int
    {
        $keys->remove($this->argument('kid'), (bool) $this->option('force'));

        $this->info("Removed key {$this->argument('kid')}.");

        return self::SUCCESS;
    }
}
