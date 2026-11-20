<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\OAuth\Oidc\UserClaims;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The OpenID Connect UserInfo endpoint at /oauth/userinfo (OpenID Connect Core
 * 1.0, section 5.3). It returns the same claims as the id_token, for an access
 * token that holds the openid scope.
 */
class UserinfoController extends Controller
{
    public function __invoke(Request $request, UserClaims $claims): JsonResponse
    {
        $user = $request->user();
        $token = $user->token();

        if (! $token->can('openid')) {
            return response()
                ->json(['error' => 'insufficient_scope'], 403)
                ->header('WWW-Authenticate', 'Bearer error="insufficient_scope", scope="openid"');
        }

        return response()->json(
            ['sub' => (string) $user->getKey()] + $claims->forScopes($user, $token->oauth_scopes),
            options: JSON_UNESCAPED_SLASHES,
        );
    }
}
