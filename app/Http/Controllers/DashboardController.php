<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\AuthorizedClients;
use App\Services\GroupMembershipResolver;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private readonly GroupMembershipResolver $groups,
        private readonly AuthorizedClients $clients,
    ) {}

    public function index(): Factory|View
    {
        if (! Auth::check()) {
            return view('landing');
        }

        // Direct groups are roots; inherited ones nest under the group they came through.
        $map = $this->groups->effectiveMembership(Auth::user());
        $groups = Group::query()->whereIn('id', array_keys($map))->orderBy('name')->get();
        $children = $groups->groupBy(fn (Group $group) => $map[$group->id] ?? '');
        $directGroups = $children->pull('', collect());

        $clients = $this->clients->for(Auth::user());

        return view('dashboard', ['directGroups' => $directGroups, 'children' => $children, 'clients' => $clients]);
    }
}
