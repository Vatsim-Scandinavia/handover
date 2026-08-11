<?php
namespace App\Services;

use App\User;
use Illuminate\Support\Facades\DB;

class GroupMembershipResolver
{
    /**
     * A user's effective group memberships, rolled up the nesting DAG.
     *
     * @return array<string, ?string> groupId => viaGroupId. A null value means the
     *   user is a DIRECT member; a non-null value is one child group the membership
     *   rolled up through (the first parent reached during BFS).
     */
    public function effectiveMembership(User $user): array
    {
        $map = [];
        $queue = [];
        foreach ($user->groups()->pluck('groups.id') as $id) {
            $map[$id] = null; // seeds are direct
            $queue[] = $id;
        }
        if ($queue === []) {
            return [];
        }

        // child_id => [parent_id, ...]  — load the whole (small) edge set once.
        $parentsOf = [];
        foreach (DB::table('group_hierarchy')->get(['parent_id', 'child_id']) as $edge) {
            $parentsOf[$edge->child_id][] = $edge->parent_id;
        }

        // Upward BFS. The map's keys double as the visited set, so direct wins
        // (seeds inserted first, keys never overwritten) and any graph terminates.
        while ($queue !== []) {
            $current = array_shift($queue);
            foreach ($parentsOf[$current] ?? [] as $parent) {
                if (!array_key_exists($parent, $map)) {
                    $map[$parent] = $current;
                    $queue[] = $parent;
                }
            }
        }

        return $map;
    }

    /** @return list<string> */
    public function effectiveGroupIds(User $user): array
    {
        return array_keys($this->effectiveMembership($user));
    }
}
