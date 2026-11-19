<?php

namespace App\Http\Controllers;

use App\Models\Passport\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Passport\ClientRepository;

class OAuthClientController extends Controller
{
    /**
     * Grant types an admin can give any client, with their labels. Clients
     * from before 4.0.0 may also hold grant types Handover does not support.
     * An admin can keep or remove those, but not add them.
     */
    private const GRANT_TYPES = [
        'authorization_code' => 'Authorization code',
        'refresh_token' => 'Refresh token',
        'client_credentials' => 'Client credentials',
    ];

    /** Grant types the form ticks for a new client, as createAuthorizationCodeGrantClient() sets them. */
    private const DEFAULT_GRANT_TYPES = ['authorization_code', 'refresh_token'];

    public function __construct(private ClientRepository $clients) {}

    /**
     * Render the admin OAuth client management page.
     */
    public function index(): View
    {
        return view('admin.oauth-clients', [
            'grantTypes' => self::GRANT_TYPES,
            'defaultGrantTypes' => self::DEFAULT_GRANT_TYPES,
        ]);
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
     * Create a confidential, first-party client and return its one-time
     * plaintext secret (Passport 13 stores only the hash).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateClient($request);

        $client = $this->clients->createAuthorizationCodeGrantClient($validated['name'], $validated['redirect_uris']);

        if (isset($validated['grant_types'])) {
            $client->forceFill(['grant_types' => $validated['grant_types']])->save();
        }

        return response()->json([
            'client' => $this->present($client),
            'secret' => $client->plainSecret,
        ], 201);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $this->validateClient($request, $client);

        $client->forceFill($validated)->save();

        return response()->json($this->present($client));
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
     * @return array{name: string, redirect_uris: string[], grant_types?: string[]}
     */
    private function validateClient(Request $request, ?Client $client = null): array
    {
        $allowedGrantTypes = [...array_keys(self::GRANT_TYPES), ...($client?->grant_types ?? [])];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'redirect_uris' => ['required', 'list', 'min:1'],
            'redirect_uris.*' => ['required', 'url', 'max:2048', 'distinct'],
            'grant_types' => ['sometimes', 'list', 'min:1'],
            'grant_types.*' => ['required', 'string', 'distinct', Rule::in($allowedGrantTypes)],
        ]);
    }

    /**
     * @return array{id: string, name: string, redirect_uris: string[], grant_types: string[], revoked: bool}
     */
    private function present(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'redirect_uris' => $client->redirect_uris,
            'grant_types' => $client->grant_types,
            'revoked' => (bool) $client->revoked,
        ];
    }
}
