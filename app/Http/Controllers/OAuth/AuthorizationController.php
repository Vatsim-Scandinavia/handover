<?php

namespace App\Http\Controllers\OAuth;

use App\OAuth\ScopeRepository;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\Http\Controllers\AuthorizationController as PassportAuthorizationController;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the client policy before Passport approves a sign-in. Every client
 * skips consent, so every successful authorize request passes through here.
 */
class AuthorizationController extends PassportAuthorizationController
{
    public function __construct(
        AuthorizationServer $server,
        StatefulGuard $guard,
        ClientRepository $clients,
        private ScopeRepository $scopes,
    ) {
        parent::__construct($server, $guard, $clients);
    }

    /**
     * Ignore prompt=consent. Passport would show its approval screen and
     * approve through ApproveAuthorizationController, past approveRequest.
     */
    public function authorize(
        ServerRequestInterface $psrRequest,
        Request $request,
        ResponseInterface $psrResponse,
        AuthorizationViewResponse $viewResponse
    ): Response|AuthorizationViewResponse {
        if ($request->has('prompt')) {
            $request->merge(['prompt' => $request->string('prompt')->explode(' ')->reject(fn (string $value): bool => $value === 'consent')->join(' ')]);
        }

        return parent::authorize($psrRequest, $request, $psrResponse, $viewResponse);
    }

    protected function approveRequest(AuthorizationRequestInterface $authRequest, ResponseInterface $psrResponse): Response
    {
        $client = $this->clients->find($authRequest->getClient()->getIdentifier());
        $policy = $client->policy;

        // Every client has a policy. Fail closed if one is missing.
        if ($policy === null) {
            abort(403, 'This client has no client policy.');
        }

        // Show the refusal here rather than redirect with access_denied, so
        // the member learns why and the relying party needs no error handling.
        // ScopeRepository::finalizeScopes checks both rules again at the token endpoint.
        if (! $policy->admits($this->guard->user())) {
            return response()->view('oauth.refused', ['client' => $client], 403);
        }

        // Report invalid_scope to the redirect URI with the state, as Passport
        // does for login_required. League adds the state only in its own checks.
        try {
            $authRequest->setScopes($this->scopes->allowedScopes(
                $authRequest->getScopes(),
                $policy,
                $authRequest->getRedirectUri() ?? Arr::wrap($authRequest->getClient()->getRedirectUri())[0],
            ));
        } catch (LeagueException $e) {
            $e->setPayload(['state' => $authRequest->getState(), ...$e->getPayload()]);

            throw new OAuthServerException($e, $authRequest->getGrantTypeId() === 'implicit');
        }

        return parent::approveRequest($authRequest, $psrResponse);
    }
}
