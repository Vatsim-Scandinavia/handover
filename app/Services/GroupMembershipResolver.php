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

        // child_id => [parent_id, ...]. Load the whole (small) edge set once.
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

    /**
     * Everyone who is a member of $group, directly or by roll-up from a
     * descendant. The inverse of effectiveMembership: instead of walking a
     * user's memberships upward, we walk $group's nesting DAG downward and
     * collect the direct members of every group in that closure.
     *
     * @return array<int, list<string>> userId => the group ids, within
     *   $group's downward closure (the group itself plus its descendants),
     *   where that user is a DIRECT member, i.e. the enrolment source(s) of
     *   their membership here. A source equal to $group->id means direct;
     *   any other id is a descendant they roll up through.
     */
    public function transitiveMembers(\App\Models\Group $group): array
    {
        // parent_id => [child_id, ...]. Load the whole (small) edge set once.
        $childrenOf = [];
        foreach (DB::table('group_hierarchy')->get(['parent_id', 'child_id']) as $edge) {
            $childrenOf[$edge->parent_id][] = $edge->child_id;
        }

        // Downward BFS. The closure's keys double as the visited set, so a
        // group reached by several paths (diamond) is enqueued once and any
        // graph terminates.
        $closure = [$group->id => true];
        $queue = [$group->id];
        while ($queue !== []) {
            $current = array_shift($queue);
            foreach ($childrenOf[$current] ?? [] as $child) {
                if (!array_key_exists($child, $closure)) {
                    $closure[$child] = true;
                    $queue[] = $child;
                }
            }
        }

        // One pass over the direct memberships of every group in the closure.
        $map = [];
        foreach (
            DB::table('group_user')->whereIn('group_id', array_keys($closure))->get(['user_id', 'group_id'])
            as $row
        ) {
            $map[$row->user_id][] = $row->group_id;
        }

        return $map;
    }
}
