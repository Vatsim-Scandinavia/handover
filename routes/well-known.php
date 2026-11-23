<?php

declare(strict_types=1);

use App\Http\Controllers\JwksController;
use App\Http\Controllers\OAuth\DiscoveryController;

/*
|--------------------------------------------------------------------------
| Well-known Routes
|--------------------------------------------------------------------------
|
| Public, stateless metadata under /.well-known. These routes get no
| middleware group, so they set no cookies and can be cached.
|
*/

Route::get('/.well-known/jwks.json', JwksController::class)->name('jwks');
Route::get('/.well-known/openid-configuration', DiscoveryController::class)->name('oidc.discovery');
