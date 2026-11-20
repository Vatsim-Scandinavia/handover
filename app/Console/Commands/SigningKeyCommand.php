<?php

namespace App\Console\Commands;

use App\OAuth\SigningKeys\SigningKeyException;
use App\OAuth\SigningKeys\SigningKeyStore;
use Illuminate\Console\Command;

/**
 * A command on the signing key store. A refused key operation prints its
 * reason and fails, without a stack trace.
 */
abstract class SigningKeyCommand extends Command
{
    public function handle(SigningKeyStore $keys): int
    {
        try {
            return $this->handleKeys($keys);
        } catch (SigningKeyException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    abstract protected function handleKeys(SigningKeyStore $keys): int;
}
