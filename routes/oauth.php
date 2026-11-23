<?php

declare(strict_types=1);

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
    ->middleware(['cors:access-token', 'auth:api'])
    ->name('oidc.userinfo');

// Passport registers the token route. Public clients call it from the browser.
Route::getRoutes()->getByName('passport.token')->middleware('cors:client');

// The cors middleware answers these preflights.
Route::options('token', fn () => response()->noContent())->middleware('cors:client');
Route::options('userinfo', fn () => response()->noContent())->middleware('cors:access-token');
