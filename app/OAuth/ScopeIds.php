<?php

declare(strict_types=1);

namespace App\OAuth;

use League\OAuth2\Server\Entities\ScopeEntityInterface;

final class ScopeIds
{
    /**
     * The identifiers of league scope entities.
     *
     * @param  ScopeEntityInterface[]  $scopes
     * @return list<string>
     */
    public static function of(array $scopes): array
    {
        return array_values(array_map(fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(), $scopes));
    }

    /**
     * Whether the league scope entities include the scope $id.
     *
     * @param  ScopeEntityInterface[]  $scopes
     */
    public static function includes(array $scopes, string $id): bool
    {
        return in_array($id, self::of($scopes), true);
    }
}
