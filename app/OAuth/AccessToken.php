<?php

namespace App\OAuth;

use App\OAuth\SigningKeys\ActiveKeySigner;
use DateTimeImmutable;
use Laravel\Passport\Bridge\AccessToken as PassportAccessToken;
use Lcobucci\JWT\Builder;

/**
 * Passport's access token, signed by the active key with its kid in the header.
 * The claims are the ones league/oauth2-server's AccessTokenTrait sets.
 */
class AccessToken extends PassportAccessToken
{
    public function toString(): string
    {
        $now = new DateTimeImmutable();

        return app(ActiveKeySigner::class)->sign(fn (Builder $builder) => $builder
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo($this->getUserIdentifier() ?? $this->getClient()->getIdentifier())
            ->withClaim('scopes', $this->getScopes())
        )->toString();
    }
}
