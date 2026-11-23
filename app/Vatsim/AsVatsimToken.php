<?php

declare(strict_types=1);

namespace App\Vatsim;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * Maps a member's VATSIM Connect token to the access_token, refresh_token
 * and token_expires columns. Null clears all three.
 *
 * @implements CastsAttributes<AccessToken|null, AccessTokenInterface|null>
 */
final class AsVatsimToken implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?AccessToken
    {
        if (! is_string($attributes['access_token'] ?? null) || $attributes['access_token'] === '') {
            return null;
        }

        return new AccessToken(array_filter([
            'access_token' => $attributes['access_token'],
            'refresh_token' => $attributes['refresh_token'] ?? null,
            'expires' => $attributes['token_expires'] ?? null,
        ], fn (mixed $value): bool => $value !== null));
    }

    /**
     * @return array{access_token: string|null, refresh_token: string|null, token_expires: int|null}
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        return [
            'access_token' => $value?->getToken(),
            'refresh_token' => $value?->getRefreshToken(),
            'token_expires' => $value?->getExpires(),
        ];
    }
}
