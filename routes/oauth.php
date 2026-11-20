<?php

use App\Http\Controllers\OAuth\UserinfoController;

/*
|--------------------------------------------------------------------------
| OAuth Routes
|--------------------------------------------------------------------------
|
| Handover's own endpoints under /oauth, next to the ones Passport
| registers. They are stateless and take a bearer token.
|
*/

Route::match(['get', 'post'], 'userinfo', UserinfoController::class)
    ->middleware('auth:api')
    ->name('oidc.userinfo');
