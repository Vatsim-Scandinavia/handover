<?php

declare(strict_types=1);

namespace App\OAuth\Oidc;

use App\OAuth\ScopeIds;
use App\OAuth\SigningKeys\ActiveKeySigner;
use App\User;
use DateTimeImmutable;
use DateTimeInterface;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Encoding\JoseEncoder;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;

/**
 * The token response, with an id_token (OpenID Connect Core 1.0, section 3.1.3.3)
 * when the authorization code grant grants the openid scope. A refresh returns
 * no id_token, which OpenID Connect allows.
 */
class IdTokenResponse extends BearerTokenResponse
{
    public function __construct(
        private readonly AuthCodeRepository $authCodes,
        private readonly UserClaims $userClaims,
    ) {}

    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        $codeId = $this->redeemedAuthCodeId();
        if (! ScopeIds::includes($accessToken->getScopes(), 'openid') || $codeId === null) {
            return [];
        }

        $user = User::find($accessToken->getUserIdentifier());
        if ($user === null) {
            return [];
        }

        return ['id_token' => $this->idToken($accessToken, $user, $codeId)];
    }

    private function idToken(AccessTokenEntityInterface $accessToken, User $user, string $codeId): string
    {
        $claims = array_filter($this->authCodes->signInParameters($codeId), fn (int|string|null $value): bool => $value !== null)
            + ['at_hash' => $this->atHash($accessToken->toString())]
            + $this->userClaims->forScopes($user, ScopeIds::of($accessToken->getScopes()));

        // Not injected: the authorization server outlives a request, and the
        // signer must read the scoped key store per request to follow rotation.
        return resolve(ActiveKeySigner::class)->sign(function (Builder $builder) use ($accessToken, $user, $claims): Builder {
            $builder = $builder
                ->issuedBy(Issuer::url())
                ->permittedFor($accessToken->getClient()->getIdentifier())
                ->relatedTo((string) $user->getKey())
                ->issuedAt($this->wholeSeconds(new DateTimeImmutable))
                ->expiresAt($this->wholeSeconds($accessToken->getExpiryDateTime()));

            foreach ($claims as $name => $value) {
                $builder = $builder->withClaim($name, $value);
            }

            return $builder;
        })->toString();
    }

    /**
     * The id of the auth code this token request redeems, or null for any other grant.
     * League has already validated the code by the time the response is built.
     */
    private function redeemedAuthCodeId(): ?string
    {
        $request = request();
        $code = $request->input('code');

        if ($request->input('grant_type') !== 'authorization_code' || ! is_string($code)) {
            return null;
        }

        return json_decode($this->decrypt($code))->auth_code_id ?? null;
    }

    /** The left half of the SHA-256 of the access token, base64url-encoded (OpenID Connect Core 1.0, section 3.1.3.6). */
    private function atHash(string $accessToken): string
    {
        return (new JoseEncoder)->base64UrlEncode(substr(hash('sha256', $accessToken, true), 0, 16));
    }

    /** Some relying parties reject the fractional NumericDate values lcobucci writes for microseconds. */
    private function wholeSeconds(DateTimeInterface $time): DateTimeImmutable
    {
        return new DateTimeImmutable('@'.$time->getTimestamp());
    }
}
