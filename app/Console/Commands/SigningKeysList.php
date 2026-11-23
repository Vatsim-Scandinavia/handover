<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\OAuth\SigningKeys\SigningKey;
use App\OAuth\SigningKeys\SigningKeyStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Description('List the published signing keys and their states')]
#[Signature('signing-keys:list')]
class SigningKeysList extends SigningKeyCommand
{
    protected function handleKeys(SigningKeyStore $keys): int
    {
        $this->table(['Kid', 'State', 'Since', 'Imported'], array_map(fn (SigningKey $key): array => [
            $key->kid,
            $key->state->value,
            $key->since->toIso8601String(),
            $key->imported ? 'yes' : 'no',
        ], $keys->published()));

        return self::SUCCESS;
    }
}
