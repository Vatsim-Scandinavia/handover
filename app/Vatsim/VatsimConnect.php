<?php

declare(strict_types=1);

namespace App\Vatsim;

use League\OAuth2\Client\Provider\AbstractProvider;
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
     * @return array<string, mixed>
     */
    public function resourceOwner(AccessToken $token): array
    {
        /** @var array<string, mixed> */
        return $this->provider->getResourceOwner($token)->toArray();
    }
}
