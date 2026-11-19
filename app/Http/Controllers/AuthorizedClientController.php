<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Laravel\Passport\Token;

class AuthorizedClientController extends Controller
{
    /**
     * List the third-party OAuth clients the authenticated user has authorized.
     *
     * Replaces Passport's deprecated GET /oauth/tokens endpoint. A client counts
     * while it holds a live access token or a live refresh token, since access
     * tokens expire after an hour. Personal access and password clients are left
     * out, as /oauth/tokens did. Passport 13's firstParty() is not used: it means
     * "has no owner", which is true for every client here.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $clients = $request->user()->tokens()
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
            ->filter(fn (?Client $client): bool => $client !== null && ! $client->revoked
                && ! $client->hasGrantType('personal_access') && ! $client->hasGrantType('password'))
            ->unique('id')
            ->map(fn (Client $client): array => [
                'name' => $client->name,
                'url' => $client->redirect_uris[0] ?? null,
            ])
            ->values();

        return response()->json($clients);
    }
}
