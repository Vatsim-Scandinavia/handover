<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\Passport\Client;
use App\OAuth\Oidc\IdTokenHint;
use App\OAuth\SigningKeys\SigningKeyStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends the Handover session (OpenID Connect RP-Initiated Logout 1.0). It serves
 * the end_session_endpoint, Handover's own logout button and the old /logout path.
 *
 * Handover logs the member out without asking when a verified id_token_hint names
 * them, or when they confirm with Handover's CSRF token. Otherwise it asks first.
 * Logout revokes no tokens and leaves the VATSIM SSO session alone.
 *
 * A relying party's POST reaches this controller as a GET, see RedirectLogoutPostToGet.
 * A POST with a CSRF token comes from Handover's own forms, so this route skips the
 * CSRF middleware and checks the token here. A POST with a wrong token gets the
 * confirmation page instead of the 419 error page.
 */
class LogoutController extends Controller
{
    public function __invoke(Request $request, SigningKeyStore $keys): Response
    {
        $hintJwt = $this->param($request, 'id_token_hint');
        $hint = $hintJwt === null ? null : IdTokenHint::verify($hintJwt, $keys);
        $clientId = $this->param($request, 'client_id');

        if ($hint instanceof IdTokenHint && $clientId !== null && $hint->clientId !== $clientId) {
            return $this->errorPage('The logout request names two different services.');
        }

        $client = $this->activeClient($clientId ?? $hint?->clientId);
        $redirectUri = $this->param($request, 'post_logout_redirect_uri');

        if ($redirectUri !== null && ! $client?->policy->allowsPostLogoutRedirectUri($redirectUri)) {
            return $this->errorPage("Handover can't send you back to the service, because the service did not register the address it asked for.");
        }

        $state = $this->param($request, 'state');

        if (Auth::check() && ($hint?->subject === (string) Auth::id() || $this->confirmed($request))) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if (Auth::check()) {
            return response()->view('oauth.logout', [
                'client' => $client,
                'redirectUri' => $redirectUri,
                'state' => $state,
            ]);
        }

        if ($redirectUri === null) {
            return response()->view('oauth.logged-out');
        }

        return redirect()->away($this->withState($redirectUri, $state));
    }

    /** A request parameter, from the query or the form body, when it is a non-empty string. */
    private function param(Request $request, string $name): ?string
    {
        $value = $request->input($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function activeClient(?string $id): ?Client
    {
        return $id === null ? null : Client::query()->with('policy')->where('revoked', false)->find($id);
    }

    /** Whether the member confirmed on Handover's own form, which carries the CSRF token. */
    private function confirmed(Request $request): bool
    {
        $token = $request->input('_token');

        return $request->isMethod('POST') && is_string($token) && hash_equals($request->session()->token(), $token);
    }

    /** The URI with the state added to its query, before any fragment. Without a state, the URI is unchanged. */
    private function withState(string $uri, ?string $state): string
    {
        if ($state === null) {
            return $uri;
        }

        [$base, $fragment] = array_pad(explode('#', $uri, 2), 2, null);

        return $base.(str_contains($base, '?') ? '&' : '?').http_build_query(['state' => $state]).($fragment === null ? '' : '#'.$fragment);
    }

    private function errorPage(string $message): Response
    {
        return response()->view('oauth.logout-error', ['message' => $message], 400);
    }
}
