<?php

namespace App\OAuth;

use App\OAuth\Oidc\UserClaims;
use App\OAuth\SigningKeys\ActiveKeySigner;
use App\User;
use DateTimeImmutable;
use Laravel\Passport\Bridge\AccessToken as PassportAccessToken;
use Lcobucci\JWT\Builder;

/**
 * Passport's access token, signed by the active key with its kid in the header.
 * The claims are the ones league/oauth2-server's AccessTokenTrait sets, plus
 * groups when the groups scope is granted.
 */
class AccessToken extends PassportAccessToken
{
    private ?string $jwt = null;

    /**
     * The signed token. It is built once, so the at_hash of an id_token matches
     * the access_token in the same response.
     */
    public function toString(): string
    {
        return $this->jwt ??= $this->sign();
    }

    private function sign(): string
    {
        $now = new DateTimeImmutable();
        $groups = $this->groupIds();

        return app(ActiveKeySigner::class)->sign(function (Builder $builder) use ($now, $groups): Builder {
            $builder = $builder
                ->permittedFor($this->getClient()->getIdentifier())
                ->identifiedBy($this->getIdentifier())
                ->issuedAt($now)
                ->canOnlyBeUsedAfter($now)
                ->expiresAt($this->getExpiryDateTime())
                ->relatedTo($this->getUserIdentifier() ?? $this->getClient()->getIdentifier())
                ->withClaim('scopes', $this->getScopes());

            // The list is fixed at issue time, so it can be up to one token lifetime stale.
            return $groups === null ? $builder : $builder->withClaim('groups', $groups);
        })->toString();
    }

    /** @return ?list<string> The member's group ids, or null when the groups scope is not granted. */
    private function groupIds(): ?array
    {
        if (! ScopeIds::includes($this->getScopes(), 'groups') || $this->getUserIdentifier() === null) {
            return null;
        }

        $user = User::find($this->getUserIdentifier());

        return $user === null ? null : app(UserClaims::class)->groupIds($user);
    }
}
