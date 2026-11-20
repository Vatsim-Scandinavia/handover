<?php

namespace App\OAuth\SigningKeys;

use RuntimeException;

/**
 * A signing key operation was refused or the key store is unusable. The message is
 * written for the operator who ran the command.
 */
class SigningKeyException extends RuntimeException
{
}
