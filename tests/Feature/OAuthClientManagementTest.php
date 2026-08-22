<?php

namespace Tests\Feature;

use App\Models\Group;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;
use Tests\TestCase;

class OAuthClientManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create();
        $group = Group::factory()->admin()->create();
        $group->members()->attach($admin->id, ['created_at' => now()]);
        $this->actingAs($admin);

        return $admin;
    }

    private function createClientViaApi(string $name = 'Test Service'): array
    {
        return $this->postJson(route('admin.oauth-clients.store'), [
            'name' => $name,
            'redirect' => 'https://service.test/callback',
        ])->json();
    }

    public function test_guest_is_unauthorized(): void
    {
        $this->getJson(route('admin.oauth-clients.data'))->assertUnauthorized();
    }

    public function test_non_admin_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.oauth-clients.index'))->assertForbidden();
        $this->getJson(route('admin.oauth-clients.data'))->assertForbidden();
    }

    public function test_admin_can_view_the_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.oauth-clients.index'))->assertOk();
    }

    public function test_admin_can_create_a_client_and_sees_the_secret_once(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('admin.oauth-clients.store'), [
            'name' => 'vACC Norway',
            'redirect' => 'https://vacc-norway.test/callback',
        ]);

        $response->assertCreated()
            ->assertJsonPath('client.name', 'vACC Norway')
            ->assertJsonPath('client.redirect', 'https://vacc-norway.test/callback')
            ->assertJsonMissingPath('client.secret');

        $clientId = $response->json('client.id');
        $plainSecret = $response->json('secret');

        // Legacy schema: integer primary key.
        $this->assertTrue(ctype_digit((string) $clientId));
        $this->assertNotEmpty($plainSecret);

        // Passport 13 stores only the hash of the secret.
        $stored = DB::table('oauth_clients')->where('id', $clientId)->value('secret');
        $this->assertNotSame($plainSecret, $stored);
        $this->assertTrue(Hash::check($plainSecret, $stored));
    }

    public function test_admin_can_list_clients_without_secrets(): void
    {
        $this->actingAsAdmin();
        $this->createClientViaApi('Alpha');
        $this->createClientViaApi('Beta');

        $response = $this->getJson(route('admin.oauth-clients.data'))->assertOk();

        $this->assertCount(2, $response->json());
        $this->assertEqualsCanonicalizing(
            ['Alpha', 'Beta'],
            array_column($response->json(), 'name'),
        );
        $this->assertArrayNotHasKey('secret', $response->json()[0]);
    }

    public function test_admin_can_update_a_client(): void
    {
        $this->actingAsAdmin();
        $id = $this->createClientViaApi()['client']['id'];

        $this->putJson(route('admin.oauth-clients.update', $id), [
            'name' => 'Renamed',
            'redirect' => 'https://renamed.test/callback',
        ])->assertOk()
            ->assertJsonPath('name', 'Renamed')
            ->assertJsonPath('redirect', 'https://renamed.test/callback');
    }

    public function test_admin_can_regenerate_a_secret(): void
    {
        $this->actingAsAdmin();
        $created = $this->createClientViaApi();
        $id = $created['client']['id'];
        $originalSecret = $created['secret'];

        $response = $this->postJson(route('admin.oauth-clients.secret', $id))->assertOk();
        $newSecret = $response->json('secret');

        $this->assertNotEmpty($newSecret);
        $this->assertNotSame($originalSecret, $newSecret);

        $stored = DB::table('oauth_clients')->where('id', $id)->value('secret');
        $this->assertTrue(Hash::check($newSecret, $stored));
    }

    public function test_admin_can_delete_a_client(): void
    {
        $this->actingAsAdmin();
        $id = $this->createClientViaApi()['client']['id'];

        $this->deleteJson(route('admin.oauth-clients.destroy', $id))->assertNoContent();

        // delete() is a revoke; the client drops out of the active list.
        $this->assertTrue((bool) DB::table('oauth_clients')->where('id', $id)->value('revoked'));
        $this->assertCount(0, $this->getJson(route('admin.oauth-clients.data'))->json());
    }

    public function test_create_validates_name_and_redirect(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('admin.oauth-clients.store'), ['name' => '', 'redirect' => 'not-a-url'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'redirect']);
    }
}
