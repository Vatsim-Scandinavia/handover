<?php

declare(strict_types=1);

namespace App\OAuth\Oidc;

use App\Listeners\RecordAuthTime;
use App\OAuth\ScopeIds;
use Laravel\Passport\Bridge\AuthCodeRepository as PassportAuthCodeRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;

/**
 * Passport's auth code repository, which also stores the nonce and auth_time
 * of an OpenID Connect sign-in with the code.
 *
 * The nonce is read from the current request. That works because Passport issues
 * the code during the GET /oauth/authorize request that carries it: every client
 * skips consent, and AuthorizationController ignores prompt=consent.
 */
class AuthCodeRepository extends PassportAuthCodeRepository
{
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        parent::persistNewAuthCode($authCodeEntity);

        if (! ScopeIds::includes($authCodeEntity->getScopes(), 'openid')) {
            return;
        }

        $nonce = request()->query('nonce');

        Passport::authCode()->newQuery()->whereKey($authCodeEntity->getIdentifier())->update([
            'nonce' => is_string($nonce) && $nonce !== '' ? $nonce : null,
            'auth_time' => session(RecordAuthTime::SESSION_KEY),
        ]);
    }

    /**
     * The nonce and auth_time stored with an auth code. Either may be null.
     *
     * @return array{nonce: ?string, auth_time: ?int}
     */
    public function signInParameters(string $codeId): array
    {
        $row = Passport::authCode()->newQuery()->whereKey($codeId)->first(['nonce', 'auth_time']);

        return [
            'nonce' => $row?->nonce,
            'auth_time' => $row?->auth_time === null ? null : (int) $row->auth_time,
        ];
    }
}
