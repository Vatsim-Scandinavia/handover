<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\GroupManagerService;
use App\Support\EditMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupHierarchyController extends Controller
{
    public function __construct(private readonly GroupManagerService $service) {}

    // Make {group} a member of another group (edge: parent = selected, child = {group}).
    public function store(Request $request, Group $group)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'parent_id' => ['required', 'uuid', 'exists:groups,id'],
        ]);
        $parentId = $data['parent_id'];
        $parent = Group::whereKey($parentId)->firstOrFail();

        if ($parentId === $group->id) {
            return back()->withErrors(['parent_id' => "{$group->name} cannot be a member of itself."]);
        }
        if ($this->wouldCreateCycle(childId: $group->id, parentId: $parentId)) {
            return back()->withErrors(['parent_id' => "{$parent->name} is already a member of {$group->name}, directly or through other groups."]);
        }

        // Idempotent: primary key (parent_id, child_id) makes a duplicate a no-op.
        DB::table('group_hierarchy')->insertOrIgnore([
            'parent_id' => $parentId,
            'child_id' => $group->id,
        ]);
        $this->service->incrementCacheVersion();

        return EditMode::keep(back()->with('success', "Added {$group->name} to {$parent->name}."));
    }

    // Take {group} out of a group it is a member of.
    public function destroy(Request $request, Group $group, string $parent): RedirectResponse
    {
        $this->requireAdmin($request);

        DB::table('group_hierarchy')
            ->where('parent_id', $parent)
            ->where('child_id', $group->id)
            ->delete();
        $this->service->incrementCacheVersion();
        $name = Group::find($parent)->name ?? 'the group';

        return EditMode::keep(back()->with('success', "Removed {$group->name} from {$name}."));
    }

    // Add another group as a member of {group} (edge: parent = {group}, child = selected).
    public function storeChild(Request $request, Group $group)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'child_id' => ['required', 'uuid', 'exists:groups,id'],
        ]);
        $childId = $data['child_id'];
        $child = Group::whereKey($childId)->firstOrFail();

        if ($childId === $group->id) {
            return back()->withErrors(['child_id' => "{$group->name} cannot be a member of itself."]);
        }
        if ($this->wouldCreateCycle(childId: $childId, parentId: $group->id)) {
            return back()->withErrors(['child_id' => "{$group->name} is already a member of {$child->name}, directly or through other groups."]);
        }

        // Idempotent: primary key (parent_id, child_id) makes a duplicate a no-op.
        DB::table('group_hierarchy')->insertOrIgnore([
            'parent_id' => $group->id,
            'child_id' => $childId,
        ]);
        $this->service->incrementCacheVersion();

        return EditMode::keep(back()->with('success', "Added {$child->name} to {$group->name}."));
    }

    // Take a member group out of {group}.
    public function destroyChild(Request $request, Group $group, string $child): RedirectResponse
    {
        $this->requireAdmin($request);

        DB::table('group_hierarchy')
            ->where('parent_id', $group->id)
            ->where('child_id', $child)
            ->delete();
        $this->service->incrementCacheVersion();
        $name = Group::find($child)->name ?? 'the group';

        return EditMode::keep(back()->with('success', "Removed {$name} from {$group->name}."));
    }

    /**
     * Adding edge (parent=P, child=C) closes a cycle iff C is already reachable
     * going upward (child -> parent) from P. Walk upward from P; if we reach C, reject.
     */
    private function wouldCreateCycle(string $childId, string $parentId): bool
    {
        $parentsOf = [];
        foreach (DB::table('group_hierarchy')->get(['parent_id', 'child_id']) as $edge) {
            $parentsOf[$edge->child_id][] = $edge->parent_id;
        }

        $queue = [$parentId];
        $seen = [$parentId => true];
        while ($queue !== []) {
            $node = array_shift($queue);
            if ($node === $childId) {
                return true;
            }
            foreach ($parentsOf[$node] ?? [] as $up) {
                if (! isset($seen[$up])) {
                    $seen[$up] = true;
                    $queue[] = $up;
                }
            }
        }

        return false;
    }

    private function requireAdmin(Request $request): void
    {
        if (! $request->attributes->get('is_group_admin')) {
            abort(403);
        }
    }
}
