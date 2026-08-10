<?php

namespace Tests\Feature\Passport;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class ClientCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The application uses the legacy Passport schema where oauth_clients.id
     * is an auto-incrementing integer. Passport 13 defaults to UUID client
     * ids, which cannot be stored in that column, so client creation must
     * keep producing sequential integer ids.
     */
    public function test_creates_client_with_auto_incrementing_integer_id(): void
    {
        $clients = app(ClientRepository::class);

        $first = $clients->createAuthorizationCodeGrantClient(
            'First Client',
            ['https://first.test/callback'],
        );
        $second = $clients->createAuthorizationCodeGrantClient(
            'Second Client',
            ['https://second.test/callback'],
        );

        $this->assertTrue(
            ctype_digit((string) $first->id),
            'Client id should be an integer, got: '.$first->id,
        );
        $this->assertEquals($first->id + 1, $second->id);

        // Redirect URIs are persisted to the legacy single "redirect" column.
        $this->assertDatabaseHas('oauth_clients', [
            'id' => $first->id,
            'name' => 'First Client',
            'redirect' => 'https://first.test/callback',
        ]);
    }
}
