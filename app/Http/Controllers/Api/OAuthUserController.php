<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use App\Http\Resources\UserCollection;

class OAuthUserController
{
    public function view(Request $request)
    {
        return Response::json(['status' => 'success', 'data' => new UserCollection($request->user())]);
    }
}

