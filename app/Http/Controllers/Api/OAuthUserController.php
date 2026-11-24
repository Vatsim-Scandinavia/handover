<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserCollection;
use App\Services\GroupMembershipResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class OAuthUserController
{
    public function __construct(private readonly GroupMembershipResolver $groups) {}

    public function view(Request $request)
    {
        return Response::json(['status' => 'success', 'data' => new UserCollection($request->user(), $this->groups)]);
    }
}
