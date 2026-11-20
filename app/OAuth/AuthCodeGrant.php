<?php

namespace App\OAuth;

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant as LeagueAuthCodeGrant;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The authorization code grant, with PKCE S256 required of public clients.
 * League already refuses a public client that sends no code challenge, but
 * accepts the plain method. Confidential clients keep PKCE optional.
 */
class AuthCodeGrant extends LeagueAuthCodeGrant
{
    public function validateAuthorizationRequest(ServerRequestInterface $request): AuthorizationRequestInterface
    {
        $authorizationRequest = parent::validateAuthorizationRequest($request);

        if (! $authorizationRequest->getClient()->isConfidential() && $authorizationRequest->getCodeChallengeMethod() !== 'S256') {
            throw OAuthServerException::invalidRequest('code_challenge_method', 'Public clients must use the S256 code challenge method.');
        }

        return $authorizationRequest;
    }
}
