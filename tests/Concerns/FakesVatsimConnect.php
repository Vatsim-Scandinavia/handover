<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Vatsim\VatsimConnect;
use ArrayObject;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * Answers VatsimConnect's HTTP calls with queued responses, so tests run the
 * real OAuth client against a fake VATSIM Connect.
 */
trait FakesVatsimConnect
{
    /** @var ArrayObject<int, array{request: RequestInterface}>|null */
    private ?ArrayObject $vatsimHistory = null;

    protected function fakeVatsim(Response|Throwable ...$responses): void
    {
        $history = new ArrayObject;
        $handler = HandlerStack::create(new MockHandler(array_values($responses)));
        $handler->push(Middleware::history($history));
        /** @var ArrayObject<int, array{request: RequestInterface}> $history */
        $this->vatsimHistory = $history;

        $this->app->when(VatsimConnect::class)
            ->needs(AbstractProvider::class)
            ->give(fn (): GenericProvider => new GenericProvider([
                'clientId' => 'handover',
                'clientSecret' => 'secret',
                'redirectUri' => 'https://handover.test/login',
                'urlAuthorize' => 'https://vatsim.test/oauth/authorize',
                'urlAccessToken' => 'https://vatsim.test/oauth/token',
                'urlResourceOwnerDetails' => 'https://vatsim.test/api/user',
                'scopeSeparator' => ' ',
            ], ['httpClient' => new Client(['handler' => $handler])]));
    }

    /**
     * @return list<RequestInterface>
     */
    protected function vatsimRequests(): array
    {
        return array_map(fn (array $entry): RequestInterface => $entry['request'], array_values($this->vatsimHistory?->getArrayCopy() ?? []));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function vatsimTokenResponse(array $overrides = []): Response
    {
        return $this->vatsimJson([
            'token_type' => 'Bearer',
            'access_token' => 'vatsim-access',
            'refresh_token' => 'vatsim-refresh',
            'expires_in' => 3600,
            ...$overrides,
        ]);
    }

    /**
     * A VATSIM Connect /api/user body for a member who granted every scope.
     *
     * @param  array<string, mixed>  $vatsim  overrides for the data.vatsim block
     * @return array<string, mixed>
     */
    protected function vatsimUser(int $cid, array $vatsim = []): array
    {
        return ['data' => [
            'cid' => (string) $cid,
            'personal' => [
                'name_first' => 'ola',
                'name_last' => 'nordmann',
                'name_full' => 'ola nordmann',
                'email' => 'ola@example.com',
                'country' => ['id' => 'NO', 'name' => 'Norway'],
            ],
            'vatsim' => [
                'rating' => ['id' => 5, 'short' => 'C1', 'long' => 'Enroute Controller'],
                'pilotrating' => ['id' => 1, 'short' => 'PPL', 'long' => 'Private Pilot Licence'],
                'region' => ['id' => 'EMEA', 'name' => 'Europe, Middle East and Africa'],
                'division' => ['id' => 'EUD', 'name' => 'Europe (except UK)'],
                'subdivision' => ['id' => 'SCA', 'name' => 'Scandinavia'],
                ...$vatsim,
            ],
            'oauth' => ['token_valid' => 'true'],
        ]];
    }

    /**
     * @param  array<string, mixed>  $vatsim  overrides for the data.vatsim block
     */
    protected function vatsimUserResponse(int $cid, array $vatsim = []): Response
    {
        return $this->vatsimJson($this->vatsimUser($cid, $vatsim));
    }

    /**
     * @param  array<mixed>  $body
     */
    protected function vatsimJson(array $body, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
    }
}
