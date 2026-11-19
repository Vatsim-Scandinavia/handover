<?php

namespace Tests\Feature;

use App\Models\Group;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
            'redirect_uris' => ['https://service.test/callback'],
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
            'redirect_uris' => ['https://vacc-norway.test/callback'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('client.name', 'vACC Norway')
            ->assertJsonPath('client.redirect_uris', ['https://vacc-norway.test/callback'])
            ->assertJsonPath('client.grant_types', ['authorization_code', 'refresh_token'])
            ->assertJsonMissingPath('client.secret');

        $clientId = $response->json('client.id');
        $plainSecret = $response->json('secret');

        $this->assertTrue(Str::isUuid($clientId, 7));
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
            'redirect_uris' => ['https://renamed.test/callback'],
            'grant_types' => ['authorization_code'],
        ])->assertOk()
            ->assertJsonPath('name', 'Renamed')
            ->assertJsonPath('redirect_uris', ['https://renamed.test/callback'])
            ->assertJsonPath('grant_types', ['authorization_code']);
    }

    public function test_admin_can_create_a_client_with_chosen_grant_types(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('admin.oauth-clients.store'), [
            'name' => 'Machine',
            'redirect_uris' => ['https://machine.test/callback'],
            'grant_types' => ['client_credentials'],
        ])->assertCreated()
            ->assertJsonPath('client.grant_types', ['client_credentials']);
    }

    public function test_edits_keep_every_redirect_uri(): void
    {
        $this->actingAsAdmin();
        $uris = ['https://one.test/callback', 'https://two.test/callback', 'https://three.test/callback'];
        $id = $this->postJson(route('admin.oauth-clients.store'), [
            'name' => 'Many',
            'redirect_uris' => $uris,
        ])->json('client.id');

        $listed = collect($this->getJson(route('admin.oauth-clients.data'))->json())->firstWhere('id', $id);
        $this->assertSame($uris, $listed['redirect_uris']);

        $this->putJson(route('admin.oauth-clients.update', $id), [
            'name' => 'Renamed',
            'redirect_uris' => $listed['redirect_uris'],
            'grant_types' => $listed['grant_types'],
        ])->assertOk()
            ->assertJsonPath('redirect_uris', $uris);

        $this->assertSame($uris, Client::find($id)->redirect_uris);
    }

    public function test_admin_can_edit_a_legacy_client_with_an_integer_id(): void
    {
        $this->actingAsAdmin();
        $grants = ['authorization_code', 'client_credentials', 'implicit', 'refresh_token', 'urn:ietf:params:oauth:grant-type:device_code'];
        Client::factory()->create(['id' => '5', 'grant_types' => $grants, 'redirect_uris' => ['https://cc.test/callback']]);

        $this->putJson(route('admin.oauth-clients.update', 5), [
            'name' => 'Control Center',
            'redirect_uris' => ['https://cc.test/callback', 'https://cc.test/other'],
            'grant_types' => $grants,
        ])->assertOk()
            ->assertJsonPath('id', '5')
            ->assertJsonPath('grant_types', $grants);

        $this->postJson(route('admin.oauth-clients.secret', 5))->assertOk();
    }

    public function test_rejects_unknown_grant_types(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('admin.oauth-clients.store'), [
            'name' => 'Bad',
            'redirect_uris' => ['https://bad.test/callback'],
            'grant_types' => ['magic'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['grant_types.0']);
    }

    public function test_legacy_grant_types_can_be_kept_but_not_added(): void
    {
        $this->actingAsAdmin();

        $this->postJson(route('admin.oauth-clients.store'), [
            'name' => 'New',
            'redirect_uris' => ['https://new.test/callback'],
            'grant_types' => ['authorization_code', 'implicit'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['grant_types.1']);

        Client::factory()->create(['id' => '6', 'grant_types' => ['authorization_code', 'implicit', 'refresh_token']]);

        $this->putJson(route('admin.oauth-clients.update', 6), [
            'name' => 'Forum',
            'redirect_uris' => ['https://forum.test/callback'],
            'grant_types' => ['authorization_code', 'implicit', 'password'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['grant_types.2'])
            ->assertJsonMissingValidationErrors(['grant_types.1']);
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

        $this->postJson(route('admin.oauth-clients.store'), ['name' => '', 'redirect_uris' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'redirect_uris']);

        $this->postJson(route('admin.oauth-clients.store'), ['name' => 'X', 'redirect_uris' => ['https://ok.test', 'not-a-url']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['redirect_uris.1']);
    }
}
