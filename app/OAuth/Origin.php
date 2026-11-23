<?php

namespace App\OAuth;

use League\Uri\Uri;

/**
 * Web origins (RFC 6454) for public clients: where their redirect URIs may
 * point, and the allowed origins that may call the token and userinfo
 * endpoints. See CONTEXT.md.
 */
final class Origin
{
    /**
     * The URI's origin as a browser sends it in the Origin header:
     * lowercase scheme and host, and the port only when it is not the
     * scheme's default. Null when the URI is not http or https.
     */
    public static function of(string $uri): ?string
    {
        $parsed = Uri::tryNew($uri);

        return in_array(strtolower((string) $parsed?->getScheme()), ['http', 'https'], true) ? $parsed->getOrigin() : null;
    }

    /**
     * The value in the form of(), when it is an origin alone: no user, path,
     * query or fragment, apart from a trailing slash. Null otherwise.
     */
    public static function normalise(string $value): ?string
    {
        $uri = Uri::tryNew($value);

        if (!$uri instanceof Uri
            || $uri->getUserInfo() !== null
            || $uri->getQuery() !== null
            || $uri->getFragment() !== null
            || ! in_array($uri->getPath(), ['', '/'], true)) {
            return null;
        }

        return self::of($value);
    }

    /**
     * Whether a public client may use the URI: https, or http on a loopback
     * host (localhost, 127.0.0.1 or [::1]) for development.
     */
    public static function isAllowedForPublicClients(string $uri): bool
    {
        $origin = self::of($uri);

        return $origin !== null
            && (str_starts_with($origin, 'https://') || preg_match('#^http://(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$#', $origin) === 1);
    }
}
