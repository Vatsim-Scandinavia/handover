<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;

class OAuthUserController
{
    public function view(Request $request)
    {
        $account = $request->user();
        $return = [];

        $return['id'] = $account->id;
        $return['first_name'] = $account->first_name;
        $return['last_name'] = $account->last_name;
        $return['full_name'] = $account->full_name;
        $return['email'] = $account->email;

        // Here we can serve more data if we need to do that sometime in future.

        return Response::json($return);
    }
}

