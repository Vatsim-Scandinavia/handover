<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\AuthorizedClients;
use App\Services\GroupMembershipResolver;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index(): Factory|View
    {
        if (! Auth::check()) {
            return view('landing');
        }

        // Direct groups are roots; inherited ones nest under the group they came through.
        $map = resolve(GroupMembershipResolver::class)->effectiveMembership(Auth::user());
        $groups = Group::whereIn('id', array_keys($map))->orderBy('name')->get();
        $children = $groups->groupBy(fn (Group $group) => $map[$group->id] ?? '');
        $directGroups = $children->pull('', collect());

        $clients = resolve(AuthorizedClients::class)->for(Auth::user());

        return view('dashboard', ['directGroups' => $directGroups, 'children' => $children, 'clients' => $clients]);
    }

    public function privacy(): Factory|View
    {
        return view('dpp');
    }
}
