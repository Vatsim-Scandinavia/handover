<?php

namespace Tests\Feature;

use App\Models\Group;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PublicClientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create();
        Group::factory()->admin()->create()->members()->attach($admin->id, ['created_at' => now()]);
        $this->actingAs($admin);
    }

    private function createPublicClient(array $fields = []): TestResponse
    {
        return $this->postJson(route('admin.oauth-clients.store'), $fields + [
            'name' => 'vcas-ok',
            'confidential' => false,
            'redirect_uris' => ['https://vcas-ok.test/callback'],
            'allowed_scopes' => ['openid'],
        ]);
    }

    private function updateClient(string $id, array $fields): TestResponse
    {
        return $this->putJson(route('admin.oauth-clients.update', $id), $fields + [
            'name' => 'vcas-ok',
            'redirect_uris' => ['https://vcas-ok.test/callback'],
        ]);
    }

    public function test_a_public_client_has_no_secret(): void
    {
        $response = $this->createPublicClient()
            ->assertCreated()
            ->assertJsonPath('client.confidential', false)
            ->assertJsonPath('secret', null);

        $this->assertNull(DB::table('oauth_clients')->where('id', $response->json('client.id'))->value('secret'));
    }

    public function test_a_public_client_cannot_get_a_secret(): void
    {
        $id = $this->createPublicClient()->json('client.id');

        $this->postJson(route('admin.oauth-clients.secret', $id))->assertStatus(422);

        $this->assertNull(DB::table('oauth_clients')->where('id', $id)->value('secret'));
    }

    public function test_a_public_client_may_only_have_the_authorization_code_and_refresh_grants(): void
    {
        $this->createPublicClient(['grant_types' => ['authorization_code', 'client_credentials']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['grant_types.1'])
            ->assertJsonMissingValidationErrors(['grant_types.0']);

        $id = $this->createPublicClient(['grant_types' => ['authorization_code']])
            ->assertCreated()
            ->json('client.id');

        $this->updateClient($id, ['grant_types' => ['refresh_token', 'client_credentials']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['grant_types.1']);
    }

    public function test_a_public_client_redirects_only_to_https_or_a_loopback_host(): void
    {
        $this->createPublicClient(['redirect_uris' => [
            'https://vcas-ok.test/callback',
            'http://localhost:5173/callback',
            'http://127.0.0.1:5173/callback',
            'http://[::1]:5173/callback',
            'http://vcas-ok.test/callback',
            'http://127.0.0.2/callback',
            'com.example.app:/callback',
        ]])->assertStatus(422)
            ->assertJsonValidationErrors(['redirect_uris.4', 'redirect_uris.5', 'redirect_uris.6'])
            ->assertJsonMissingValidationErrors(['redirect_uris.0', 'redirect_uris.1', 'redirect_uris.2', 'redirect_uris.3']);
    }

    public function test_a_confidential_client_keeps_its_redirect_uri_rules(): void
    {
        $this->createPublicClient([
            'confidential' => true,
            'redirect_uris' => ['http://forum.test/callback'],
        ])->assertCreated();
    }

    public function test_allowed_origins_are_stored_as_scheme_host_and_port(): void
    {
        $this->createPublicClient(['allowed_origins' => [
            'HTTPS://VCAS-OK.test:443/',
            'https://vcas-ok.test',
            'http://localhost:5173',
            'https://staging.vcas-ok.test:8443',
        ]])->assertCreated()
            ->assertJsonPath('client.allowed_origins', [
                'https://vcas-ok.test',
                'http://localhost:5173',
                'https://staging.vcas-ok.test:8443',
            ]);
    }

    public function test_allowed_origins_default_to_the_redirect_uris_origins(): void
    {
        $this->createPublicClient(['redirect_uris' => [
            'https://vcas-ok.test/callback',
            'https://vcas-ok.test/silent-renew',
            'http://localhost:5173/callback',
        ]])->assertCreated()
            ->assertJsonPath('client.allowed_origins', ['https://vcas-ok.test', 'http://localhost:5173']);
    }

    public function test_an_allowed_origin_is_only_an_https_or_loopback_origin(): void
    {
        $this->createPublicClient(['allowed_origins' => [
            'https://vcas-ok.test',
            'http://127.0.0.1:5173',
            'https://vcas-ok.test/app',
            'http://vcas-ok.test',
            'https://vcas-ok.test?x=1',
            'vcas-ok.test',
            'https://vcas-ok.test?',
            'https://user@vcas-ok.test',
            'https://vcas-ok.test#top',
        ]])->assertStatus(422)
            ->assertJsonValidationErrors(array_map(fn (int $i): string => "allowed_origins.{$i}", range(2, 8)))
            ->assertJsonMissingValidationErrors(['allowed_origins.0', 'allowed_origins.1']);
    }

    public function test_admin_can_edit_a_public_clients_allowed_origins(): void
    {
        $id = $this->createPublicClient()->json('client.id');

        $this->updateClient($id, ['allowed_origins' => ['https://other.test']])
            ->assertOk()
            ->assertJsonPath('allowed_origins', ['https://other.test']);

        $this->updateClient($id, ['allowed_origins' => []])
            ->assertOk()
            ->assertJsonPath('allowed_origins', []);
    }

    public function test_a_confidential_client_has_no_allowed_origins(): void
    {
        $this->createPublicClient([
            'confidential' => true,
            'allowed_origins' => ['https://vcas-ok.test'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['allowed_origins']);

        $this->createPublicClient(['confidential' => true])
            ->assertCreated()
            ->assertJsonPath('client.allowed_origins', []);
    }

    public function test_the_client_type_is_fixed(): void
    {
        $id = $this->createPublicClient()->json('client.id');

        $this->updateClient($id, ['confidential' => true])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['confidential']);

        $this->assertNull(DB::table('oauth_clients')->where('id', $id)->value('secret'));
    }

    public function test_a_public_clients_post_logout_redirect_uris_follow_its_redirect_uri_rules(): void
    {
        $this->createPublicClient(['post_logout_redirect_uris' => [
            'https://vcas-ok.test/logged-out',
            'http://localhost:5173/logged-out',
            'http://vcas-ok.test/logged-out',
            'not-a-url',
        ]])->assertStatus(422)
            ->assertJsonValidationErrors(['post_logout_redirect_uris.2', 'post_logout_redirect_uris.3'])
            ->assertJsonMissingValidationErrors(['post_logout_redirect_uris.0', 'post_logout_redirect_uris.1']);
    }

    public function test_post_logout_redirect_uris_are_not_prefilled(): void
    {
        $this->createPublicClient()
            ->assertCreated()
            ->assertJsonPath('client.post_logout_redirect_uris', []);
    }
}
