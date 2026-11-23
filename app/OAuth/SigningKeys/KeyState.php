<?php

declare(strict_types=1);

namespace App\OAuth\SigningKeys;

/**
 * Where a signing key is in its rotation. Every state is published in the JWKS;
 * only the active key signs. See docs/adr/0003-signing-keys-and-rotation.md.
 */
enum KeyState: string
{
    case Staged = 'staged';
    case Active = 'active';
    case Retired = 'retired';
}
