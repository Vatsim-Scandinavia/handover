<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\OAuth\Oidc\Issuer;
use App\OAuth\Oidc\UserClaims;
use Illuminate\Http\JsonResponse;
use Laravel\Passport\Passport;

/**
 * The OpenID Provider metadata at /.well-known/openid-configuration
 * (OpenID Connect Discovery 1.0). It lists the fields Discovery requires and
 * the ones Handover's relying parties read.
 */
class DiscoveryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'issuer' => Issuer::url(),
            'authorization_endpoint' => Issuer::route('passport.authorizations.authorize'),
            'token_endpoint' => Issuer::route('passport.token'),
            'userinfo_endpoint' => Issuer::route('oidc.userinfo'),
            'jwks_uri' => Issuer::route('jwks'),
            'scopes_supported' => Passport::scopeIds(),
            'claims_supported' => [
                'iss', 'sub', 'aud', 'exp', 'iat', 'auth_time', 'nonce', 'at_hash',
                ...UserClaims::SUPPORTED,
            ],
            'response_types_supported' => ['code'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'code_challenge_methods_supported' => ['S256'],
        ], options: JSON_UNESCAPED_SLASHES);
    }
}
