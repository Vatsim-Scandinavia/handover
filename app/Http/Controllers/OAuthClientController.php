<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Passport\Client;
use App\OAuth\Origin;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Scope;

class OAuthClientController extends Controller
{
    /**
     * Grant types an admin can give any client, with their labels. Clients
     * from before 4.0.0 may also hold grant types Handover does not support.
     * An admin can keep or remove those, but not add them.
     */
    private const array GRANT_TYPES = [
        'authorization_code' => 'Authorization code',
        'refresh_token' => 'Refresh token',
        'client_credentials' => 'Client credentials',
    ];

    /**
     * Grant types the form ticks for a new client, as createAuthorizationCodeGrantClient()
     * sets them. They are also the only grant types a public client may have.
     */
    private const array DEFAULT_GRANT_TYPES = ['authorization_code', 'refresh_token'];

    public function __construct(private readonly ClientRepository $clients) {}

    /**
     * Render the admin OAuth client management page.
     */
    public function index(): View
    {
        return view('admin.oauth-clients', [
            'grantTypes' => self::GRANT_TYPES,
            'defaultGrantTypes' => self::DEFAULT_GRANT_TYPES,
            'scopes' => collect(Passport::scopes())->mapWithKeys(fn (Scope $scope): array => [$scope->id => $scope->description]),
            'groups' => Group::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * List all active OAuth clients. Secrets are hashed and never returned.
     */
    public function data(): JsonResponse
    {
        $clients = Client::query()
            ->with('policy.requiredGroups')
            ->where('revoked', false)
            ->orderBy('name')
            ->get()
            ->map(fn (Client $client): array => $this->present($client));

        return response()->json($clients);
    }

    /**
     * Create a first-party client. A confidential client gets a secret, which
     * the response holds once in plaintext (Passport 13 stores only the hash).
     * A public client gets none.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateClient($request);

        $client = DB::transaction(function () use ($validated): Client {
            $client = $this->clients->createAuthorizationCodeGrantClient(
                $validated['name'],
                $validated['redirect_uris'],
                confidential: $validated['confidential'] ?? true,
            );

            // A new public client may call Handover from where it receives its
            // codes. updateFromAdmin() reduces the URIs to their origins.
            if (! $client->confidential() && ! isset($validated['allowed_origins'])) {
                $validated['allowed_origins'] = $validated['redirect_uris'];
            }

            if (isset($validated['grant_types'])) {
                $client->forceFill(['grant_types' => $validated['grant_types']])->save();
            }

            $client->policy->updateFromAdmin($validated);

            return $client;
        });

        return response()->json([
            'client' => $this->present($client),
            'secret' => $client->plainSecret,
        ], 201);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $this->validateClient($request, $client);

        DB::transaction(function () use ($client, $validated): void {
            $client->forceFill(Arr::only($validated, ['name', 'redirect_uris', 'grant_types']))->save();
            $client->policy->updateFromAdmin($validated);
        });

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
        // A secret would make a public client confidential, and its type is fixed.
        if (! $client->confidential()) {
            return response()->json(['message' => 'A public client has no secret.'], 422);
        }

        $this->clients->regenerateSecret($client);

        return response()->json(['secret' => $client->plainSecret]);
    }

    /**
     * Admins pick a new client's scopes by hand, so they are required on create.
     * A public client gets stricter redirect URIs and grant types, and may
     * have allowed origins.
     *
     * @return array{name: string, confidential?: bool, redirect_uris: string[], grant_types?: string[], allowed_scopes?: string[], allowed_origins?: string[], post_logout_redirect_uris?: string[], single_session?: bool, required_groups?: string[]}
     */
    private function validateClient(Request $request, ?Client $client = null): array
    {
        $public = $client instanceof Client ? ! $client->confidential() : ! $request->boolean('confidential', true);
        $allowedGrantTypes = $public
            ? self::DEFAULT_GRANT_TYPES
            : [...array_keys(self::GRANT_TYPES), ...($client?->grant_types ?? [])];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // The type is fixed once the client exists.
            'confidential' => ['sometimes', 'boolean', function (string $attribute, mixed $value, Closure $fail) use ($client): void {
                if ($client instanceof Client && (bool) $value !== $client->confidential()) {
                    $fail('A client cannot change between confidential and public.');
                }
            }],
            'redirect_uris' => ['required', 'list', 'min:1'],
            'redirect_uris.*' => $this->redirectUriRules($public),
            'grant_types' => ['sometimes', 'list', 'min:1'],
            'grant_types.*' => ['required', 'string', 'distinct', Rule::in($allowedGrantTypes)],
            'allowed_scopes' => [$client instanceof Client ? 'sometimes' : 'required', 'list', 'min:1'],
            'allowed_scopes.*' => ['required', 'string', 'distinct', Rule::in(Passport::scopeIds())],
            // Only a public client calls Handover from the browser.
            'allowed_origins' => $public ? ['sometimes', 'list'] : ['prohibited'],
            'allowed_origins.*' => ['required', 'string', 'max:2048', $this->allowedOrigin()],
            'post_logout_redirect_uris' => ['sometimes', 'list'],
            'post_logout_redirect_uris.*' => $this->redirectUriRules($public),
            'single_session' => ['sometimes', 'boolean'],
            'required_groups' => ['sometimes', 'list'],
            'required_groups.*' => ['required', 'string', 'distinct', Rule::exists('groups', 'id')],
        ], [
            'redirect_uris.required' => 'Add at least one redirect URI.',
            'redirect_uris.min' => 'Add at least one redirect URI.',
            'redirect_uris.*.required' => 'Enter a redirect URI.',
        ], [
            'redirect_uris.*' => 'redirect URI',
        ]);
    }

    /**
     * The rules for one redirect URI or post-logout redirect URI. Both follow
     * the same rules for each client type.
     *
     * @return list<mixed>
     */
    private function redirectUriRules(bool $public): array
    {
        return ['required', 'url', 'max:2048', 'distinct', ...($public ? [$this->publicClientUri()] : [])];
    }

    /** A public client's URIs must use https, or http on a loopback host for development. */
    private function publicClientUri(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && ! Origin::isAllowedForPublicClients($value)) {
                $fail('The :attribute must use https, or http on localhost, 127.0.0.1 or [::1] for development.');
            }
        };
    }

    /** An allowed origin is an origin alone, which a public client may use. */
    private function allowedOrigin(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && (Origin::normalise($value) === null || ! Origin::isAllowedForPublicClients($value))) {
                $fail('The :attribute must be an origin such as https://example.test, with no path. Only https, or http on localhost, 127.0.0.1 or [::1] for development, is allowed.');
            }
        };
    }

    /**
     * @return array{id: string, name: string, confidential: bool, redirect_uris: string[], grant_types: string[], allowed_scopes: string[], allowed_origins: string[], post_logout_redirect_uris: string[], single_session: bool, required_groups: string[], revoked: bool}
     */
    private function present(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'confidential' => $client->confidential(),
            'redirect_uris' => $client->redirect_uris,
            'grant_types' => $client->grant_types,
            ...$client->policy->toAdminArray(),
            'revoked' => (bool) $client->revoked,
        ];
    }
}
