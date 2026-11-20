<?php

namespace App\Http\Controllers;

use App\OAuth\SigningKeys\SigningKey;
use App\OAuth\SigningKeys\SigningKeyStore;
use Illuminate\Http\JsonResponse;

/**
 * The JSON Web Key Set at /.well-known/jwks.json. Relying parties verify token
 * signatures with it. It lists staged and retired keys too, so a key is in every
 * cache before it signs and stays there until its tokens expire.
 */
class JwksController extends Controller
{
    public function __invoke(SigningKeyStore $keys): JsonResponse
    {
        return response()
            ->json(['keys' => array_map(fn (SigningKey $key) => $key->publicJwk(), $keys->published())])
            ->header('Cache-Control', 'public, max-age='.SigningKeyStore::STAGING_PERIOD_SECONDS);
    }
}
