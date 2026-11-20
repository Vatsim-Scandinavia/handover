<?php

namespace App\OAuth\Oidc;

/**
 * Handover's OpenID Connect issuer identifier. Every id_token carries it as
 * iss, and discovery lists every endpoint under it, so it comes from APP_URL
 * rather than from the host of the current request.
 */
final class Issuer
{
    public static function url(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /** An absolute URL under the issuer for a named route. */
    public static function route(string $name): string
    {
        return self::url().route($name, [], false);
    }
}
