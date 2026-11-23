<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;
use App\Models\Group;
use App\Services\AuthorizedClients;
use App\Services\GroupMembershipResolver;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index()
    {
        if (!Auth::check()) {
            return view('landing');
        }

        // Direct groups are roots; inherited ones nest under the group they came through.
        $map = app(GroupMembershipResolver::class)->effectiveMembership(Auth::user());
        $groups = Group::whereIn('id', array_keys($map))->orderBy('name')->get();
        $children = $groups->groupBy(fn (Group $group) => $map[$group->id] ?? '');
        $directGroups = $children->pull('', collect());

        $clients = app(AuthorizedClients::class)->for(Auth::user());

        return view('dashboard', compact('directGroups', 'children', 'clients'));
    }

    public function privacy(){
        return view('dpp');
    }

}
