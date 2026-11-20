<?php

namespace App\OAuth\SigningKeys;

use Carbon\CarbonImmutable;
use League\OAuth2\Server\CryptKey;
use SensitiveParameter;

/**
 * One RSA key in the store. The kid is the RFC 7638 thumbprint of its public key.
 */
final readonly class SigningKey
{
    public function __construct(
        public string $kid,
        public KeyState $state,
        #[SensitiveParameter] public string $privateKeyPem,
        /** When the key entered its current state. */
        public CarbonImmutable $since,
        /** Whether the key was adopted from Passport's original key files. */
        public bool $imported,
    ) {
    }

    /** When the key may move on: a staged key to active, a retired key to removed. */
    public function readyAt(): CarbonImmutable
    {
        return $this->since->addSeconds(match ($this->state) {
            KeyState::Staged => SigningKeyStore::STAGING_PERIOD_SECONDS,
            KeyState::Retired => SigningKeyStore::RETIREMENT_PERIOD_SECONDS,
            KeyState::Active => 0,
        });
    }

    public function publicKeyPem(): string
    {
        return openssl_pkey_get_details(openssl_pkey_get_private($this->privateKeyPem))['key'];
    }

    /** The key comes from memory, not a file, so there are no file permissions to check. */
    public function privateCryptKey(): CryptKey
    {
        return new CryptKey($this->privateKeyPem, null, false);
    }

    public function publicCryptKey(): CryptKey
    {
        return new CryptKey($this->publicKeyPem(), null, false);
    }

    /**
     * The public key as published in the JWKS.
     *
     * @return array{kty: string, use: string, alg: string, kid: string, n: string, e: string}
     */
    public function publicJwk(): array
    {
        $jwk = Jwk::fromPrivateKey($this->privateKeyPem);

        return ['kty' => $jwk['kty'], 'use' => 'sig', 'alg' => 'RS256', 'kid' => $this->kid, 'n' => $jwk['n'], 'e' => $jwk['e']];
    }
}
