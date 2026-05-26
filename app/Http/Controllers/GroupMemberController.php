<?php
namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\GroupManagerService;
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
        return view('groups.members', compact('group', 'members', 'search'));
    }

    public function store(Request $request, Group $group)
    {
        $this->requireAccess($request, $group);
        $request->validate(['cid' => ['required', 'integer']]);

        $user = User::find($request->cid);
        if (!$user) {
            abort(404, "No user found with CID {$request->cid}.");
        }

        if ($group->members()->where('user_id', $user->id)->exists()) {
            return back()->with('info', "{$user->first_name} {$user->last_name} is already a member — no action taken.");
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
            return back()->with('info', "{$user->first_name} {$user->last_name} is not a member — no action taken.");
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
