<?php

namespace App\OAuth\Oidc;

use App\OAuth\SigningKeys\SigningKeyStore;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Exception as JwtException;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;

/**
 * The id_token_hint of a logout request (OpenID Connect RP-Initiated Logout 1.0):
 * an id_token Handover issued earlier, which names the member and the client.
 *
 * Handover trusts a hint only when a published key signed it and its iss is
 * Handover's. It ignores exp, because a relying party often holds an expired
 * id_token by the time the member logs out.
 */
final readonly class IdTokenHint
{
    private function __construct(
        /** The member's CID. */
        public string $subject,
        /** The id of the client the id_token was issued to. */
        public string $clientId,
    ) {
    }

    /** The hint, or null when Handover can't verify it. */
    public static function verify(string $jwt, SigningKeyStore $keys): ?self
    {
        try {
            $token = (new Parser(new JoseEncoder()))->parse($jwt);
        } catch (JwtException) {
            return null;
        }

        $kid = $token->headers()->get('kid');
        $key = is_string($kid) ? $keys->find($kid) : null;

        if (! $token instanceof UnencryptedToken || $key === null) {
            return null;
        }

        $valid = (new Validator())->validate(
            $token,
            new SignedWith(new Sha256(), InMemory::plainText($key->publicKeyPem())),
            new IssuedBy(Issuer::url()),
        );
        $subject = $token->claims()->get('sub');
        // Handover issues each id_token to one client.
        $audience = $token->claims()->get('aud', []);
        $clientId = count($audience) === 1 ? reset($audience) : null;

        if (! $valid || ! is_string($subject) || $subject === '' || ! is_string($clientId)) {
            return null;
        }

        return new self($subject, $clientId);
    }
}
