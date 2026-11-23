<?php

namespace App\OAuth\SigningKeys;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Exception as JwtException;
use Lcobucci\JWT\Token\Parser;
use League\OAuth2\Server\AuthorizationValidators\AuthorizationValidatorInterface;
use League\OAuth2\Server\AuthorizationValidators\BearerTokenValidator;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Validates bearer access tokens against every published key, picked by the token's
 * kid. It hands the chosen key to league/oauth2-server's BearerTokenValidator, which
 * knows one key only.
 *
 * A token with no kid was signed before keys had kids, so it is checked against the
 * imported original key while that key is still published.
 */
class PublishedKeysValidator implements AuthorizationValidatorInterface
{
    public function __construct(
        private readonly AccessTokenRepositoryInterface $accessTokens,
        private readonly SigningKeyStore $keys,
    ) {
    }

    public function validateAuthorization(ServerRequestInterface $request): ServerRequestInterface
    {
        $jwt = trim((string) preg_replace('/^\s*Bearer\s/i', '', $request->getHeaderLine('authorization')));

        if ($jwt === '') {
            throw OAuthServerException::accessDenied('Missing "Bearer" token');
        }

        try {
            $kid = (new Parser(new JoseEncoder()))->parse($jwt)->headers()->get('kid');
        } catch (JwtException $e) {
            throw OAuthServerException::accessDenied($e->getMessage(), null, $e);
        }

        $key = match (true) {
            is_string($kid) => $this->keys->find($kid),
            $kid === null => $this->keys->imported(),
            default => null,
        };

        if (!$key instanceof SigningKey) {
            throw OAuthServerException::accessDenied('Access token was signed by an unknown key');
        }

        $validator = new BearerTokenValidator($this->accessTokens);
        $validator->setPublicKey($key->publicCryptKey());

        return $validator->validateAuthorization($request);
    }
}
