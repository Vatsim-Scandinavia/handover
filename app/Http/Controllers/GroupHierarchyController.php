<?php
namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\GroupManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupHierarchyController extends Controller
{
    public function __construct(private GroupManagerService $service) {}

    // Nest {group} (the child) into a parent group.
    public function store(Request $request, Group $group)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'parent_id' => ['required', 'uuid', 'exists:groups,id'],
        ]);
        $parentId = $data['parent_id'];

        if ($parentId === $group->id) {
            return back()->withErrors(['parent_id' => 'A group cannot nest into itself.']);
        }
        if ($this->wouldCreateCycle(childId: $group->id, parentId: $parentId)) {
            return back()->withErrors(['parent_id' => 'That would create a nesting cycle.']);
        }

        // Idempotent: primary key (parent_id, child_id) makes a duplicate a no-op.
        DB::table('group_hierarchy')->insertOrIgnore([
            'parent_id' => $parentId,
            'child_id'  => $group->id,
        ]);
        $this->service->incrementCacheVersion();

        return back()->with('success', 'Parent group added.');
    }

    // Remove the nesting edge (parent -> {group}).
    public function destroy(Request $request, Group $group, string $parent)
    {
        $this->requireAdmin($request);

        DB::table('group_hierarchy')
            ->where('parent_id', $parent)
            ->where('child_id', $group->id)
            ->delete();
        $this->service->incrementCacheVersion();

        return back()->with('success', 'Parent group removed.');
    }

    // Nest a child group INTO {group}. Edge is (parent = {group}, child = selected).
    public function storeChild(Request $request, Group $group)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'child_id' => ['required', 'uuid', 'exists:groups,id'],
        ]);
        $childId = $data['child_id'];

        if ($childId === $group->id) {
            return back()->withErrors(['child_id' => 'A group cannot nest into itself.']);
        }
        if ($this->wouldCreateCycle(childId: $childId, parentId: $group->id)) {
            return back()->withErrors(['child_id' => 'That would create a nesting cycle.']);
        }

        // Idempotent: primary key (parent_id, child_id) makes a duplicate a no-op.
        DB::table('group_hierarchy')->insertOrIgnore([
            'parent_id' => $group->id,
            'child_id'  => $childId,
        ]);
        $this->service->incrementCacheVersion();

        return back()->with('success', 'Child group added.');
    }

    // Remove the nesting edge ({group} -> child).
    public function destroyChild(Request $request, Group $group, string $child)
    {
        $this->requireAdmin($request);

        DB::table('group_hierarchy')
            ->where('parent_id', $group->id)
            ->where('child_id', $child)
            ->delete();
        $this->service->incrementCacheVersion();

        return back()->with('success', 'Child group removed.');
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
                if (!isset($seen[$up])) {
                    $seen[$up] = true;
                    $queue[] = $up;
                }
            }
        }

        return false;
    }

    private function requireAdmin(Request $request): void
    {
        if (!$request->attributes->get('is_group_admin')) {
            abort(403);
        }
    }
}
