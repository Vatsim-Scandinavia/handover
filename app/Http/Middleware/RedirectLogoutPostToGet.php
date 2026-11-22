<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns a relying party's POST to the end session endpoint into a GET.
 *
 * The browser sends the SameSite=Lax session cookie with a cross-site GET
 * navigation, but not with a cross-site POST. Without the cookie, Handover
 * would see nobody signed in, and the new session's cookie would replace the
 * member's. This middleware therefore runs before the session starts.
 *
 * Handover's own forms are same-site and carry a CSRF token, so they pass
 * through. A cross-site POST may carry a token too, but never the cookie.
 */
class RedirectLogoutPostToGet
{
    /** The logout request parameters Handover reads. */
    private const PARAMS = ['id_token_hint', 'client_id', 'post_logout_redirect_uri', 'state'];

    public function handle(Request $request, Closure $next): Response
    {
        $ownForm = $request->request->has('_token') && $request->cookies->has(config('session.cookie'));

        if ($request->isMethod('POST') && ! $ownForm) {
            return redirect()->route('oidc.logout', $request->only(self::PARAMS), 303);
        }

        return $next($request);
    }
}
