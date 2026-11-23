<?php

declare(strict_types=1);

namespace App\Services;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Laravel\Passport\Client;
use Laravel\Passport\Token;

class AuthorizedClients
{
    /**
     * The third-party OAuth clients a user has authorized.
     *
     * Replaces Passport's deprecated GET /oauth/tokens endpoint. A client counts
     * while it holds a live access token or a live refresh token, since access
     * tokens expire after an hour. Personal access and password clients are left
     * out, as /oauth/tokens did. Passport 13's firstParty() is not used: it means
     * "has no owner", which is true for every client here.
     *
     * @return Collection<int, array{name: string, url: ?string}>
     */
    public function for(User $user): Collection
    {
        return $user->tokens()
            ->with('client')
            ->where('revoked', false)
            ->where(fn (Builder $query) => $query
                ->where('expires_at', '>', now())
                ->orWhereExists(fn (QueryBuilder $refresh) => $refresh
                    ->from('oauth_refresh_tokens')
                    ->whereColumn('oauth_refresh_tokens.access_token_id', 'oauth_access_tokens.id')
                    ->where('oauth_refresh_tokens.revoked', false)
                    ->where('oauth_refresh_tokens.expires_at', '>', now())))
            ->get()
            ->map(fn (Token $token): ?Client => $token->client)
            ->filter(fn (?Client $client): bool => $client instanceof Client && ! $client->revoked
                && ! $client->hasGrantType('personal_access') && ! $client->hasGrantType('password'))
            ->unique('id')
            ->map(fn (Client $client): array => [
                'name' => $client->name,
                'url' => $this->origin($client->redirect_uris[0] ?? null),
            ])
            ->values();
    }

    /**
     * The site a redirect URI belongs to. The URI itself is usually the OAuth
     * callback, which errors when opened without an authorization code.
     */
    private function origin(?string $uri): ?string
    {
        $parts = parse_url($uri ?? '');
        if (! in_array($parts['scheme'] ?? null, ['http', 'https'], true) || ! isset($parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
