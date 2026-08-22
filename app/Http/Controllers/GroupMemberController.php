<?php
namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\GroupManagerService;
use App\Services\GroupMembershipResolver;
use App\User;
use Illuminate\Http\Request;

class GroupMemberController extends Controller
{
    public function __construct(private GroupManagerService $service) {}

    public function index()
    {
        $request = request();
        $slug = $request->route('group');
        $group = $slug instanceof \App\Models\Group ? $slug : \App\Models\Group::where('slug', $slug)->firstOrFail();

        $this->requireAccess($request, $group);

        $search = $request->get('search');
        $query = $group->members()->orderBy('first_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', $search . '%')
                  ->orWhere('last_name', 'like', $search . '%')
                  ->orWhere('users.id', $search);
            });
        }

        $members = $query->paginate(25)->withQueryString();
        $transitive = false;
        return view('groups.members', compact('group', 'members', 'search', 'transitive'));
    }

    /**
     * Every member of the group, including those inherited from descendant
     * groups (their members roll up here). Read-only: enrolment is managed on
     * the direct-members view.
     */
    public function all(Request $request, Group $group, GroupMembershipResolver $resolver)
    {
        $this->requireAccess($request, $group);

        // userId => enrolment source group ids (this group and/or descendants).
        $sourceMap = $resolver->transitiveMembers($group);

        $search = $request->get('search');
        $query = User::whereIn('id', array_keys($sourceMap))->orderBy('first_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', $search . '%')
                  ->orWhere('last_name', 'like', $search . '%')
                  ->orWhere('users.id', $search);
            });
        }

        $members = $query->paginate(25)->withQueryString();

        // Names for the descendant groups a membership rolls up through.
        $viaIds = collect($sourceMap)->flatten()->unique()
            ->reject(fn ($id) => $id === $group->id)->values();
        $viaNames = Group::whereIn('id', $viaIds)->pluck('name', 'id');

        // userId => ['direct' => bool, 'via' => list<string names>]
        $memberSources = collect($sourceMap)->map(function ($sources) use ($group, $viaNames) {
            return [
                'direct' => in_array($group->id, $sources, true),
                'via'    => collect($sources)
                    ->reject(fn ($id) => $id === $group->id)
                    ->map(fn ($id) => $viaNames[$id] ?? 'unknown')
                    ->values()->all(),
            ];
        })->all();

        $transitive = true;
        return view('groups.members', compact('group', 'members', 'search', 'transitive', 'memberSources'));
    }

    public function store(Request $request, Group $group)
    {
        $this->requireAccess($request, $group);

        if ($group->is_admin_group && !$request->attributes->get('is_group_admin')) {
            return back()->withErrors(['member' => 'Only admins can add members to an admin group.']);
        }

        $request->validate(['cid' => ['required', 'integer']]);

        $user = User::find($request->cid);
        if (!$user) {
            abort(404, "No user found with CID {$request->cid}.");
        }

        if ($group->members()->where('user_id', $user->id)->exists()) {
            return back()->with('info', "{$user->first_name} {$user->last_name} is already a member. No action taken.");
        }

        $group->members()->attach($user->id, [
            'added_by'   => $request->user()->id,
            'created_at' => now(),
        ]);
        $this->service->incrementCacheVersion();

        return back()->with('success', "{$user->first_name} {$user->last_name} added.");
    }

    public function destroy(Request $request, Group $group, User $user)
    {
        $this->requireAccess($request, $group);

        if (!$group->members()->where('user_id', $user->id)->exists()) {
            return back()->with('info', "{$user->first_name} {$user->last_name} is not a member. No action taken.");
        }

        if ($group->is_admin_group && $group->members()->count() === 1) {
            return back()->withErrors(['member' => 'Cannot remove the last member of an admin group.']);
        }

        $group->members()->detach($user->id);
        $this->service->incrementCacheVersion();

        return back()->with('success', "{$user->first_name} {$user->last_name} removed.");
    }

    private function requireAccess(Request $request, Group $group): void
    {
        if (!$request->attributes->get('is_group_admin') && !$this->service->canManage($request->user(), $group)) {
            abort(403);
        }
    }
}
