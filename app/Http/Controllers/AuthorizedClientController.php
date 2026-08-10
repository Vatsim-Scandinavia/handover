<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Laravel\Passport\Token;

class AuthorizedClientController extends Controller
{
    /**
     * List the third-party OAuth clients the authenticated user has authorized.
     *
     * Replaces Passport's deprecated GET /oauth/tokens endpoint. Mirrors its
     * filtering: only non-revoked, non-expired tokens whose client is neither
     * revoked nor first-party.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $clients = $request->user()->tokens()
            ->with('client')
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->get()
            ->map(fn (Token $token): ?Client => $token->client)
            ->filter(fn (?Client $client): bool => $client !== null && ! $client->revoked && ! $client->firstParty())
            ->unique('id')
            ->map(fn (Client $client): array => [
                'name' => $client->name,
                'url' => $client->redirect_uris[0] ?? null,
            ])
            ->values();

        return response()->json($clients);
    }
}
