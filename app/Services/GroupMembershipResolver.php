<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Group;
use App\User;
use Illuminate\Support\Facades\DB;

class GroupMembershipResolver
{
    /**
     * A user's effective group memberships, rolled up the nesting DAG.
     *
     * @return array<string, ?string> groupId => viaGroupId. A null value means the
     *                                user is a DIRECT member; a non-null value is one child group the membership
     *                                rolled up through (the first parent reached during BFS).
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
                if (! array_key_exists($parent, $map)) {
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
     *                                  $group's downward closure (the group itself plus its descendants),
     *                                  where that user is a DIRECT member, i.e. the enrolment source(s) of
     *                                  their membership here. A source equal to $group->id means direct;
     *                                  any other id is a descendant they roll up through.
     */
    public function transitiveMembers(Group $group): array
    {
        [, $childrenOf] = $this->edges();
        $closure = $this->reach($group->id, $childrenOf);

        // One pass over the direct memberships of every group in the closure.
        $map = [];
        foreach (
            DB::table('group_user')->whereIn('group_id', $closure)->get(['user_id', 'group_id']) as $row
        ) {
            $map[$row->user_id][] = $row->group_id;
        }

        return $map;
    }

    /**
     * Member counts for many groups at once, each user counted once per group
     * however many paths lead there.
     *
     * @param  iterable<Group>  $groups
     * @return array<string, int> groupId => direct and inherited members
     */
    public function transitiveMemberCounts(iterable $groups): array
    {
        [, $childrenOf] = $this->edges();
        $closures = [];
        $touched = [];
        foreach ($groups as $group) {
            $closures[$group->id] = $this->reach($group->id, $childrenOf);
            $touched += array_fill_keys($closures[$group->id], true);
        }

        // group_id => [user_id, ...] for every group any closure touches.
        $usersOf = [];
        foreach (DB::table('group_user')->whereIn('group_id', array_keys($touched))->get(['user_id', 'group_id']) as $row) {
            $usersOf[$row->group_id][] = $row->user_id;
        }

        $counts = [];
        foreach ($closures as $id => $closure) {
            $users = [];
            foreach ($closure as $member) {
                foreach ($usersOf[$member] ?? [] as $user) {
                    $users[$user] = true;
                }
            }
            $counts[$id] = count($users);
        }

        return $counts;
    }

    /**
     * The groups $group is a member of through its direct parents, and which
     * direct parents lead to each. Direct parents are left out, even when
     * another path also reaches them.
     *
     * @return array<string, list<string>> groupId => [directParentId, ...]
     */
    public function indirectMemberOf(Group $group): array
    {
        [$parentsOf] = $this->edges();
        $direct = $parentsOf[$group->id] ?? [];

        // Walk up from each direct parent, so each ancestor records every
        // direct parent it is reached through. The graph is small.
        $map = [];
        foreach ($direct as $parent) {
            foreach (array_slice($this->reach($parent, $parentsOf), 1) as $up) {
                $map[$up][] = $parent;
            }
        }
        unset($map[$group->id]);

        return array_diff_key($map, array_flip($direct));
    }

    /**
     * The whole (small) edge set, both ways: [child_id => parent ids,
     * parent_id => child ids].
     *
     * @return array{0: array<string, list<string>>, 1: array<string, list<string>>}
     */
    private function edges(): array
    {
        $parentsOf = [];
        $childrenOf = [];
        foreach (DB::table('group_hierarchy')->get(['parent_id', 'child_id']) as $edge) {
            if (is_string($edge->child_id) && is_string($edge->parent_id)) {
                $parentsOf[$edge->child_id][] = $edge->parent_id;
                $childrenOf[$edge->parent_id][] = $edge->child_id;
            }
        }

        return [$parentsOf, $childrenOf];
    }

    /**
     * $start and every group reachable from it along $next, $start first.
     *
     * @param  array<string, list<string>>  $next
     * @return list<string>
     */
    private function reach(string $start, array $next): array
    {
        // BFS. The keys double as the visited set, so a group reached by
        // several paths (diamond) is enqueued once and any graph terminates.
        $seen = [$start => true];
        $queue = [$start];
        while ($queue !== []) {
            $current = array_shift($queue);
            foreach ($next[$current] ?? [] as $neighbour) {
                if (! array_key_exists($neighbour, $seen)) {
                    $seen[$neighbour] = true;
                    $queue[] = $neighbour;
                }
            }
        }

        return array_map(strval(...), array_keys($seen));
    }
}
