<?php

declare(strict_types=1);

namespace App\OAuth\Oidc;

use App\Services\GroupMembershipResolver;
use App\User;

/**
 * The OpenID Connect claims a member's granted scopes release. The id_token
 * and userinfo carry the same claims. See docs/integrations/oidc.md.
 */
class UserClaims
{
    /** Every claim this class can release, in the order discovery lists them. */
    public const SUPPORTED = ['name', 'given_name', 'family_name', 'email', 'groups'];

    public function __construct(private readonly GroupMembershipResolver $memberships)
    {
    }

    /**
     * The claims for the granted scopes, except sub. Claims with no value are left out.
     *
     * @param  list<string>  $scopes
     * @return array<string, mixed>
     */
    public function forScopes(User $user, array $scopes): array
    {
        $claims = [];

        if (in_array('profile', $scopes, true)) {
            $claims['name'] = trim("{$user->first_name} {$user->last_name}") ?: null;
            $claims['given_name'] = $user->first_name;
            $claims['family_name'] = $user->last_name;
        }

        if (in_array('email', $scopes, true)) {
            $claims['email'] = $user->email;
        }

        if (in_array('groups', $scopes, true)) {
            $claims['groups'] = $this->groupIds($user);
        }

        return array_filter($claims, fn ($value): bool => $value !== null);
    }

    /**
     * The ids of every group in the member's effective membership.
     *
     * @return list<string>
     */
    public function groupIds(User $user): array
    {
        return array_map(strval(...), $this->memberships->effectiveGroupIds($user));
    }
}
