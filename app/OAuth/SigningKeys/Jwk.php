<?php

namespace App\OAuth\SigningKeys;

use Lcobucci\JWT\Encoding\JoseEncoder;

/**
 * RSA public keys as JSON Web Keys (RFC 7517) and their thumbprints (RFC 7638).
 */
final class Jwk
{
    /**
     * The public half of an RSA private key, as the members a thumbprint covers.
     *
     * @return array{kty: string, n: string, e: string}
     */
    public static function fromPrivateKey(string $privateKeyPem): array
    {
        $key = openssl_pkey_get_private($privateKeyPem);
        $details = $key === false ? false : openssl_pkey_get_details($key);

        if ($details === false || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) {
            throw new SigningKeyException('The key is not a readable RSA private key.');
        }

        $encoder = new JoseEncoder();

        return [
            'kty' => 'RSA',
            'n' => $encoder->base64UrlEncode($details['rsa']['n']),
            'e' => $encoder->base64UrlEncode($details['rsa']['e']),
        ];
    }

    /**
     * The SHA-256 thumbprint of an RSA JWK. Members other than e, kty and n are ignored.
     *
     * @param  array{kty: string, n: string, e: string}  $jwk
     */
    public static function thumbprint(array $jwk): string
    {
        // RFC 7638 hashes the required members only, in lexical order, with no whitespace.
        $canonical = json_encode(
            ['e' => $jwk['e'], 'kty' => $jwk['kty'], 'n' => $jwk['n']],
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        return (new JoseEncoder())->base64UrlEncode(hash('sha256', $canonical, true));
    }
}
