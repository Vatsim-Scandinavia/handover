<?php

namespace App\Console\Commands;

use App\OAuth\SigningKeys\SigningKey;
use App\OAuth\SigningKeys\SigningKeyStore;

class SigningKeysList extends SigningKeyCommand
{
    protected $signature = 'signing-keys:list';
    protected $description = 'List the published signing keys and their states';

    protected function handleKeys(SigningKeyStore $keys): int
    {
        $this->table(['Kid', 'State', 'Since', 'Imported'], array_map(fn (SigningKey $key) => [
            $key->kid,
            $key->state->value,
            $key->since->toIso8601String(),
            $key->imported ? 'yes' : 'no',
        ], $keys->published()));

        return self::SUCCESS;
    }
}
