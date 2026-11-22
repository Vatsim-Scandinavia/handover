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
            'allowed_scopes' => ['full_name'],
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
            'allowed_scopes' => ['full_name'],
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
            'allowed_scopes' => ['full_name'],
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
            'allowed_scopes' => ['full_name'],
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
            'allowed_scopes' => ['full_name'],
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
            'allowed_scopes' => ['full_name'],
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
            ->assertJsonValidationErrors(['name', 'redirect_uris', 'allowed_scopes']);

        $this->postJson(route('admin.oauth-clients.store'), ['name' => 'X', 'redirect_uris' => ['https://ok.test', 'not-a-url'], 'allowed_scopes' => ['email']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['redirect_uris.1']);
    }

    public function test_admin_sets_the_client_policy_when_creating_a_client(): void
    {
        $this->actingAsAdmin();
        $group = Group::factory()->create();

        $this->postJson(route('admin.oauth-clients.store'), [
            'name' => 'Roster',
            'redirect_uris' => ['https://roster.test/callback'],
            'allowed_scopes' => ['email', 'groups'],
            'single_session' => false,
            'required_groups' => [$group->id],
        ])->assertCreated()
            ->assertJsonPath('client.allowed_scopes', ['email', 'groups'])
            ->assertJsonPath('client.single_session', false)
            ->assertJsonPath('client.required_groups', [$group->id]);
    }

    public function test_a_new_client_defaults_to_a_single_session_and_no_required_groups(): void
    {
        $this->actingAsAdmin();

        $this->createClientViaApi();

        $listed = $this->getJson(route('admin.oauth-clients.data'))->json(0);
        $this->assertSame(['full_name'], $listed['allowed_scopes']);
        $this->assertTrue($listed['single_session']);
        $this->assertSame([], $listed['required_groups']);
    }

    public function test_admin_can_edit_the_client_policy(): void
    {
        $this->actingAsAdmin();
        [$keep, $drop, $add] = Group::factory()->count(3)->create();
        $id = $this->postJson(route('admin.oauth-clients.store'), [
            'name' => 'Forum',
            'redirect_uris' => ['https://forum.test/callback'],
            'allowed_scopes' => ['full_name'],
            'required_groups' => [$keep->id, $drop->id],
        ])->json('client.id');

        $this->putJson(route('admin.oauth-clients.update', $id), [
            'name' => 'Forum',
            'redirect_uris' => ['https://forum.test/callback'],
            'allowed_scopes' => ['full_name', 'groups'],
            'single_session' => false,
            'required_groups' => [$keep->id, $add->id],
        ])->assertOk()
            ->assertJsonPath('allowed_scopes', ['full_name', 'groups'])
            ->assertJsonPath('single_session', false)
            ->assertJsonPath('required_groups', collect([$keep->id, $add->id])->sort()->values()->all());

        $this->putJson(route('admin.oauth-clients.update', $id), [
            'name' => 'Forum',
            'redirect_uris' => ['https://forum.test/callback'],
            'allowed_scopes' => ['full_name'],
            'single_session' => true,
            'required_groups' => [],
        ])->assertOk()
            ->assertJsonPath('single_session', true)
            ->assertJsonPath('required_groups', []);
    }

    public function test_rejects_an_empty_or_unknown_scope_list_and_unknown_groups(): void
    {
        $this->actingAsAdmin();
        $id = $this->createClientViaApi()['client']['id'];

        $this->putJson(route('admin.oauth-clients.update', $id), [
            'name' => 'X',
            'redirect_uris' => ['https://x.test/callback'],
            'allowed_scopes' => [],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['allowed_scopes']);

        $this->putJson(route('admin.oauth-clients.update', $id), [
            'name' => 'X',
            'redirect_uris' => ['https://x.test/callback'],
            'allowed_scopes' => ['email', 'magic'],
            'required_groups' => ['0190a2d4-0000-7000-8000-000000000000'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['allowed_scopes.1', 'required_groups.0']);
    }

    public function test_admin_can_set_and_edit_post_logout_redirect_uris(): void
    {
        $this->actingAsAdmin();
        $id = $this->postJson(route('admin.oauth-clients.store'), [
            'name' => 'Forum',
            'redirect_uris' => ['https://forum.test/callback'],
            'allowed_scopes' => ['openid'],
            'post_logout_redirect_uris' => ['http://forum.test/logged-out', 'https://forum.test/'],
        ])->assertCreated()
            ->assertJsonPath('client.post_logout_redirect_uris', ['http://forum.test/logged-out', 'https://forum.test/'])
            ->json('client.id');

        $this->putJson(route('admin.oauth-clients.update', $id), [
            'name' => 'Forum',
            'redirect_uris' => ['https://forum.test/callback'],
            'post_logout_redirect_uris' => ['https://forum.test/', 'not-a-url', 'https://forum.test/'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['post_logout_redirect_uris.1', 'post_logout_redirect_uris.2']);

        $this->putJson(route('admin.oauth-clients.update', $id), [
            'name' => 'Forum',
            'redirect_uris' => ['https://forum.test/callback'],
            'post_logout_redirect_uris' => [],
        ])->assertOk()
            ->assertJsonPath('post_logout_redirect_uris', []);
    }
}
