<?php

namespace App\Policies;

use App\Models\Group;
use App\Services\GroupManagerService;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    public function __construct(private GroupManagerService $service) {}

    /**
     * Whether the user may access group management at all — i.e. they are a
     * system admin, or at least one group is manageable to them via a rule.
     * Mirrors the App\Http\Middleware\ManagesGroups guard.
     */
    public function viewAny(User $user): bool
    {
        return $this->service->isAdmin($user) || ! empty($this->service->manageableGroupIds($user));
    }

    /**
     * Whether the user may manage a specific group (view its detail page,
     * its members and rules). Admins may manage every group.
     */
    public function manage(User $user, Group $group): bool
    {
        return $this->service->canManage($user, $group);
    }
}
