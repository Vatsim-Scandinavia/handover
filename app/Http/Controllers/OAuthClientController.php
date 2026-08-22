<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

class OAuthClientController extends Controller
{
    public function __construct(private ClientRepository $clients) {}

    /**
     * Render the admin OAuth client management page.
     */
    public function index(): View
    {
        return view('admin.oauth-clients');
    }

    /**
     * List all active OAuth clients. Secrets are hashed and never returned.
     */
    public function data(): JsonResponse
    {
        $clients = Client::query()
            ->where('revoked', false)
            ->orderBy('name')
            ->get()
            ->map(fn (Client $client): array => $this->present($client));

        return response()->json($clients);
    }

    /**
     * Create a confidential authorization-code client and return its
     * one-time plaintext secret (Passport 13 stores only the hash).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateClient($request);

        $client = $this->clients->createAuthorizationCodeGrantClient(
            $validated['name'],
            [$validated['redirect']],
            confidential: true,
        );

        return response()->json([
            'client' => $this->present($client),
            'secret' => $client->plainSecret,
        ], 201);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $this->validateClient($request);

        $this->clients->update($client, $validated['name'], [$validated['redirect']]);

        return response()->json($this->present($client->refresh()));
    }

    public function destroy(Client $client): JsonResponse
    {
        $this->clients->delete($client);

        return response()->json(null, 204);
    }

    /**
     * Rotate the client secret and return the new one-time plaintext value.
     */
    public function regenerateSecret(Client $client): JsonResponse
    {
        $this->clients->regenerateSecret($client);

        return response()->json(['secret' => $client->plainSecret]);
    }

    /**
     * @return array{name: string, redirect: string}
     */
    private function validateClient(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['required', 'url', 'max:2048'],
        ]);
    }

    /**
     * @return array{id: int|string, name: string, redirect: string|null, revoked: bool}
     */
    private function present(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'redirect' => $client->redirect_uris[0] ?? null,
            'revoked' => (bool) $client->revoked,
        ];
    }
}
