<?php

use App\Http\Controllers\JwksController;

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
