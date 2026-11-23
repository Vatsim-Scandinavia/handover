<?php

declare(strict_types=1);

namespace App\OAuth;

use App\Models\Passport\ClientPolicy;
use App\User;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Bridge\ScopeRepository as PassportScopeRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;

/**
 * Applies each client's policy whenever a token is issued: the scopes it may
 * have, and, for grants with a user, its required groups.
 */
class ScopeRepository extends PassportScopeRepository
{
    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null
    ): array {
        $policy = ClientPolicy::find($clientEntity->getIdentifier());

        // Tokens issued earlier are not revoked. They run out on their own.
        if ($userIdentifier !== null && ! $this->admits($policy, $userIdentifier)) {
            throw OAuthServerException::invalidGrant('The user is not in a group this client requires.');
        }

        return parent::finalizeScopes(
            $this->allowedScopes($scopes, $policy),
            $grantType,
            $clientEntity,
            $userIdentifier,
            $authCodeId,
        );
    }

    /**
     * The scopes to grant for a request. A request with no scopes gets the
     * default scopes the client is allowed. A request for any other scope
     * fails.
     *
     * @param  ScopeEntityInterface[]  $scopes
     * @return ScopeEntityInterface[]
     *
     * @throws OAuthServerException invalid_scope
     */
    public function allowedScopes(array $scopes, ?ClientPolicy $policy, ?string $redirectUri = null): array
    {
        $allowed = $policy->allowed_scopes ?? [];

        if ($scopes === []) {
            return array_map(
                fn (string $id): Scope => new Scope($id),
                array_values(array_intersect(ClientPolicy::DEFAULT_SCOPES, $allowed)),
            );
        }

        foreach ($scopes as $scope) {
            if (! in_array($scope->getIdentifier(), $allowed, true)) {
                throw OAuthServerException::invalidScope($scope->getIdentifier(), $redirectUri);
            }
        }

        return $scopes;
    }

    private function admits(?ClientPolicy $policy, string $userIdentifier): bool
    {
        $user = User::find($userIdentifier);

        // Every client has a policy. Fail closed if one is missing.
        return $policy instanceof ClientPolicy && $user !== null && $policy->admits($user);
    }
}
