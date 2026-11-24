<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\GroupManagerService;
use App\Services\GroupMembershipResolver;
use App\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GroupMemberController extends Controller
{
    public function __construct(
        private readonly GroupManagerService $service,
        private readonly GroupMembershipResolver $resolver,
    ) {}

    /**
     * Everyone who counts as a member: enrolled in the group, or in a member
     * group (their members roll up here), with where each membership comes
     * from. Also the group's member groups and the groups it is a member of.
     */
    public function index(): Factory|View
    {
        // No parameters: this overrides Controller::index(), the dashboard.
        $request = request();
        $slug = $request->route('group');
        $group = $slug instanceof Group ? $slug : Group::where('slug', $slug)->firstOrFail();

        $this->requireAccess($request, $group);
        $isAdmin = (bool) $request->attributes->get('is_group_admin');

        // userId => enrolment source group ids (this group and/or descendants).
        $sourceMap = $this->resolver->transitiveMembers($group);

        $search = $request->string('search')->toString();
        $query = User::whereIn('id', array_keys($sourceMap))->matching($search)->orderBy('first_name');

        $members = $query->paginate(25)->withQueryString();

        // Names for the descendant groups a membership rolls up through.
        $viaIds = collect($sourceMap)->flatten()->unique()
            ->reject(fn ($id): bool => $id === $group->id)->values();
        $viaNames = Group::whereIn('id', $viaIds)->pluck('name', 'id');

        // userId => ['direct' => bool, 'via' => list<string names>]
        $memberSources = collect($sourceMap)->map(fn ($sources): array => [
            'direct' => in_array($group->id, $sources, true),
            'via' => collect($sources)
                ->reject(fn ($id): bool => $id === $group->id)
                ->map(fn ($id) => $viaNames[$id] ?? 'unknown')
                ->values()->all(),
        ])->all();

        $group->load('parents', 'children');
        // Picker options: every other group, with those already linked shown
        // but not pickable. Cycles are rejected on submit.
        $memberGroupOptions = [];
        $memberOfOptions = [];
        if ($isAdmin) {
            $others = Group::where('id', '!=', $group->id)->orderBy('name')->get(['id', 'name', 'is_admin_group']);
            $option = fn (Group $g, Collection $linked): array => [
                'id' => $g->id,
                'name' => $g->name,
                'admin' => $g->is_admin_group,
                'disabled' => $linked->contains('id', $g->id),
            ];
            $memberGroupOptions = $others->map(fn (Group $g): array => $option($g, $group->children))->all();
            $memberOfOptions = $others->map(fn (Group $g): array => $option($g, $group->parents))->all();
        }

        return view('groups.members', ['group' => $group, 'members' => $members, 'search' => $search, 'memberSources' => $memberSources, 'isAdmin' => $isAdmin, 'memberGroupOptions' => $memberGroupOptions, 'memberOfOptions' => $memberOfOptions]);
    }

    /**
     * Up to ten users matching a name or CID, for the add-member picker.
     * Direct members are disabled, so the picker shows them as added.
     */
    public function candidates(Request $request, Group $group): JsonResponse
    {
        $this->requireAccess($request, $group);

        $term = $request->string('q')->trim()->toString();
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $users = User::matching($term)->orderBy('first_name')->orderBy('last_name')
            ->limit(10)->get(['id', 'first_name', 'last_name']);
        $memberIds = $group->members()->whereKey($users->modelKeys())->pluck('users.id')->all();

        return response()->json($users->map(fn (User $user): array => [
            'id' => $user->id,
            'name' => trim($user->first_name.' '.$user->last_name),
            'detail' => (string) $user->id,
            'disabled' => in_array($user->id, $memberIds, true),
        ])->values());
    }

    public function store(Request $request, Group $group)
    {
        $this->requireAccess($request, $group);

        if ($group->is_admin_group && ! $request->attributes->get('is_group_admin')) {
            return back()->withErrors(['member' => 'Only admins can add members to an admin group.']);
        }

        $request->validate(['cid' => ['required', 'integer']]);

        $user = User::find($request->cid);
        if (! $user) {
            abort(404, "No user found with CID {$request->cid}.");
        }

        if ($group->members()->where('user_id', $user->id)->exists()) {
            return back()->with('info', "{$user->first_name} {$user->last_name} is already a member. No action taken.");
        }

        $group->members()->attach($user->id, [
            'added_by' => $request->user()->id,
            'created_at' => now(),
        ]);
        $this->service->incrementCacheVersion();

        return back()->with('success', "{$user->first_name} {$user->last_name} added.");
    }

    public function destroy(Request $request, Group $group, User $user)
    {
        $this->requireAccess($request, $group);

        if (! $group->members()->where('user_id', $user->id)->exists()) {
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
        if (! $request->attributes->get('is_group_admin') && ! $this->service->canManage($request->user(), $group)) {
            abort(403);
        }
    }
}
