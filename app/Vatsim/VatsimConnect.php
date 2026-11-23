<?php

declare(strict_types=1);

namespace App\Vatsim;

use Exception;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;

/**
 * The app's only way to talk to VATSIM Connect, the OAuth server members
 * log in with. AppServiceProvider configures the provider.
 */
final readonly class VatsimConnect
{
    /**
     * @param  list<string>  $requiredScopes  scopes the member cannot untick
     */
    public function __construct(
        private AbstractProvider $provider,
        private array $requiredScopes,
    ) {}

    /**
     * @return array{url: string, state: string}
     */
    public function authorizationUrl(): array
    {
        $url = $this->provider->getAuthorizationUrl([
            'required_scopes' => implode(' ', $this->requiredScopes),
        ]);

        return ['url' => $url, 'state' => $this->provider->getState()];
    }

    public function exchangeCode(string $code): AccessToken
    {
        /** @var AccessToken */
        return $this->provider->getAccessToken('authorization_code', ['code' => $code]);
    }

    /**
     * @return AccessToken|null null when VATSIM rejects the refresh token
     *
     * @throws Exception when the refresh fails for another reason, such as an outage
     */
    public function refresh(AccessToken $token): ?AccessToken
    {
        try {
            /** @var AccessToken */
            return $this->provider->getAccessToken('refresh_token', ['refresh_token' => $token->getRefreshToken()]);
        } catch (IdentityProviderException $exception) {
            // RFC 6749, section 5.2: invalid_grant covers an expired, revoked
            // or reused refresh token.
            if ($exception->getMessage() === 'invalid_grant') {
                return null;
            }

            throw $exception;
        }
    }

    /**
     * @throws IncompleteConsent when the member withheld a scope we require
     */
    public function member(AccessToken $token): Member
    {
        return Member::fromResourceOwner($this->provider->getResourceOwner($token)->toArray());
    }
}
