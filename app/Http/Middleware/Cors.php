<?php

namespace App\Http\Middleware;

use App\Models\Passport\ClientPolicy;
use App\OAuth\Origin;
use Closure;
use Fruitcake\Cors\CorsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets browser code on other origins call a route. The parameter says which
 * origins may read the response:
 *
 * - any: every origin. Only for public metadata that carries no credentials.
 * - client: the allowed origins of the client that authenticates in the
 *   request. The check runs before the endpoint, so a refused origin can't
 *   use up a code.
 * - access-token: the allowed origins of the access token's client. With no
 *   valid token, an origin that some client allows may read the 401.
 *
 * A route answers a preflight only if it also accepts OPTIONS. The preflight
 * is allowed for an origin that some client allows, and the real request is
 * then checked against its own client. No response allows credentials.
 */
class Cors
{
    public function handle(Request $request, Closure $next, string $allow): Response
    {
        if ($allow === 'any') {
            return (new CorsService(['allowedOrigins' => ['*']]))->addActualRequestHeaders($next($request), $request);
        }

        $response = $request->headers->has('Origin') ? $this->forClient($request, $next, $allow) : $next($request);

        // The answer depends on the origin, so caches must key on it.
        $response->setVary('Origin', false);

        return $response;
    }

    private function forClient(Request $request, Closure $next, string $allow): Response
    {
        $origin = Origin::normalise((string) $request->headers->get('Origin'));

        if ($origin === null) {
            return $this->refuse();
        }

        $cors = new CorsService([
            'allowedOrigins' => [$origin],
            'allowedMethods' => ['*'],
            'allowedHeaders' => ['Authorization', 'Content-Type'],
            // Lets the client read why its credentials or token were refused.
            'exposedHeaders' => ['WWW-Authenticate'],
            'maxAge' => 3600,
        ]);

        if ($cors->isPreflightRequest($request)) {
            return ClientPolicy::anyAllowsOrigin($origin) ? $cors->handlePreflightRequest($request) : $this->refuse();
        }

        if ($allow === 'client') {
            $clientId = $request->getUser() ?? $request->input('client_id');

            return $this->allows($clientId, $origin) ? $cors->addActualRequestHeaders($next($request), $request) : $this->refuse();
        }

        $response = $next($request);
        $clientId = $request->user('api')?->token()?->oauth_client_id;

        if ($clientId === null) {
            return ClientPolicy::anyAllowsOrigin($origin) ? $cors->addActualRequestHeaders($response, $request) : $response;
        }

        return $this->allows($clientId, $origin) ? $cors->addActualRequestHeaders($response, $request) : $this->refuse();
    }

    private function allows(mixed $clientId, string $origin): bool
    {
        return is_string($clientId) && (bool) ClientPolicy::find($clientId)?->allowsOrigin($origin);
    }

    private function refuse(): Response
    {
        return response()->json([
            'error' => 'invalid_request',
            'error_description' => 'Handover does not allow this origin for this request.',
        ], 403);
    }
}
