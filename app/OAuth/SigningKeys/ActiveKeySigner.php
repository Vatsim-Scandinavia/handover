<?php

declare(strict_types=1);

namespace App\OAuth\SigningKeys;

use Closure;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder as TokenBuilder;
use Lcobucci\JWT\UnencryptedToken;

/**
 * Signs JWTs with the active key, RS256, and sets the key's kid in the header.
 * Every JWT Handover issues goes through here.
 */
class ActiveKeySigner
{
    public function __construct(private readonly SigningKeyStore $keys) {}

    /**
     * @param  Closure(Builder): Builder  $claims  Adds the claims to the builder.
     */
    public function sign(Closure $claims): UnencryptedToken
    {
        $key = $this->keys->active();
        $builder = TokenBuilder::new(new JoseEncoder, ChainedFormatter::default())->withHeader('kid', $key->kid);

        return $claims($builder)->getToken(new Sha256, InMemory::plainText($key->privateKeyPem));
    }
}
