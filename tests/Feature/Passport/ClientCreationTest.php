<?php

declare(strict_types=1);

namespace Tests\Feature\Passport;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class ClientCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_client_with_a_uuid7_id(): void
    {
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
            'First Client',
            ['https://first.test/callback', 'https://first.test/other'],
        );

        $this->assertTrue(Str::isUuid($client->id, 7), 'Client id should be a UUIDv7, got: '.$client->id);

        $client = $client->fresh();
        $this->assertSame(['https://first.test/callback', 'https://first.test/other'], $client->redirect_uris);
        $this->assertSame(['authorization_code', 'refresh_token'], $client->grant_types);
        $this->assertNull($client->owner_id);
    }
}
