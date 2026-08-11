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
use App\Services\GroupMembershipResolver;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index()
    {
        if (!Auth::check()) {
            return view('landing');
        }

        $map = app(GroupMembershipResolver::class)->effectiveMembership(Auth::user());
        $groups = collect();
        if ($map !== []) {
            $byId = Group::whereIn('id', array_keys($map))->orderBy('name')->get()->keyBy('id');
            $groups = collect($map)->map(fn ($viaId, $groupId) => [
                'group' => $byId[$groupId],
                'via'   => $viaId !== null ? ($byId[$viaId]->name ?? null) : null,
            ])->filter(fn ($row) => $row['group'] !== null)
              ->sortBy(fn ($row) => $row['group']->name)
              ->values();
        }

        return view('dashboard', compact('groups'));
    }

    public function privacy(){
        return view('dpp');
    }

}
