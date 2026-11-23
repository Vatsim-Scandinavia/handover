<?php

namespace Tests\Feature\OAuth;

use App\Models\Group;
use App\Models\Passport\Client;
use App\Models\Passport\ClientPolicy;
use App\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\RunsOAuthFlows;
use Tests\TestCase;

class ClientPolicyTest extends TestCase
{
    use RefreshDatabase, RunsOAuthFlows;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_creating_a_client_creates_its_policy(): void
    {
        $client = $this->makeClient();

        $policy = ClientPolicy::findOrFail($client->getKey());
        $this->assertTrue($policy->single_session);
        $this->assertSame([], $policy->requiredGroups->all());
    }

    public function test_a_scope_outside_the_allowed_list_fails_with_invalid_scope(): void
    {
        $client = $this->makeClient();
        $client->policy->update(['allowed_scopes' => ['full_name']]);

        $response = $this->authorizeRequest(User::factory()->create(), $client, ['scope' => 'full_name email', 'state' => 'xyz']);

        $response->assertRedirect();
        $this->assertStringStartsWith($this->redirectUri, $response->headers->get('Location'));
        $this->assertSame('invalid_scope', $this->redirectQuery($response)['error']);
        $this->assertSame('xyz', $this->redirectQuery($response)['state']);
    }

    public function test_scopes_inside_the_allowed_list_are_granted(): void
    {
        $client = $this->makeClient();
        $client->policy->update(['allowed_scopes' => ['email', 'groups']]);

        $tokens = $this->signIn(User::factory()->create(), $client, ['scope' => 'email groups']);

        $this->assertSame(['email', 'groups'], $this->jwtPayload($tokens['access_token'])['scopes']);
    }

    public function test_the_default_scopes_are_limited_to_the_allowed_list(): void
    {
        $client = $this->makeClient();
        $client->policy->update(['allowed_scopes' => ['email', 'groups']]);

        $tokens = $this->signIn(User::factory()->create(), $client);

        $this->assertSame(['email'], $this->jwtPayload($tokens['access_token'])['scopes']);
    }

    public function test_a_client_allowed_every_scope_gets_the_default_scopes(): void
    {
        $client = $this->makeClient();
        $client->policy->update(['allowed_scopes' => ['full_name', 'email', 'vatsim_details', 'country', 'groups']]);

        $tokens = $this->signIn(User::factory()->create(), $client);

        $this->assertSame(['full_name', 'email', 'vatsim_details', 'country'], $this->jwtPayload($tokens['access_token'])['scopes']);
    }

    public function test_a_client_with_no_required_groups_admits_every_member(): void
    {
        $this->signIn(User::factory()->create(), $this->makeClient());
    }

    public function test_a_member_outside_the_required_groups_sees_a_refusal_page(): void
    {
        $client = $this->clientRequiring(Group::factory()->create());

        $this->authorizeRequest(User::factory()->create(), $client)
            ->assertForbidden()
            ->assertSee("You can't sign in to Test Client", false)
            ->assertSee(route('landing'), false)     // a way to the member's groups
            ->assertSee(route('oidc.logout'), false); // and a way to log out
        $this->assertSame(0, DB::table('oauth_auth_codes')->count());
    }

    public function test_prompt_consent_still_shows_the_refusal_page(): void
    {
        $client = $this->clientRequiring(Group::factory()->create());

        $this->authorizeRequest(User::factory()->create(), $client, ['prompt' => 'consent'])
            ->assertForbidden()
            ->assertSee("You can't sign in to Test Client", false);
    }

    public function test_prompt_consent_skips_the_approval_screen(): void
    {
        $response = $this->authorizeRequest(User::factory()->create(), $this->makeClient(), ['prompt' => 'consent']);

        $response->assertRedirect();
        $this->assertArrayHasKey('code', $this->redirectQuery($response));
    }

    public function test_a_direct_member_of_a_required_group_may_sign_in(): void
    {
        $group = Group::factory()->create();
        $client = $this->clientRequiring(Group::factory()->create(), $group);

        $this->signIn($this->memberOf($group), $client);
    }

    public function test_a_member_of_a_nested_group_may_sign_in(): void
    {
        $parent = Group::factory()->create();
        $child = Group::factory()->create();
        $child->parents()->attach($parent->id);

        $this->signIn($this->memberOf($child), $this->clientRequiring($parent));
    }

    public function test_a_refresh_fails_once_the_member_leaves_the_required_groups(): void
    {
        $group = Group::factory()->create();
        $user = $this->memberOf($group);
        $client = $this->clientRequiring($group);
        $tokens = $this->signIn($user, $client);

        $group->members()->detach($user->id);

        $this->refresh($client, $tokens['refresh_token'])->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');
    }

    public function test_a_refresh_fails_once_the_member_leaves_a_nested_group(): void
    {
        $parent = Group::factory()->create();
        $child = Group::factory()->create();
        $child->parents()->attach($parent->id);
        $client = $this->clientRequiring($parent);
        $user = $this->memberOf($child);
        $tokens = $this->signIn($user, $client);

        $this->refresh($client, $tokens['refresh_token'])->assertOk();
        $tokens = $this->signIn($user, $client);
        $child->parents()->detach($parent->id);

        $this->refresh($client, $tokens['refresh_token'])->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');
    }

    public function test_a_refresh_succeeds_while_the_member_is_in_a_required_group(): void
    {
        $group = Group::factory()->create();
        $user = $this->memberOf($group);
        $client = $this->clientRequiring($group);
        $tokens = $this->signIn($user, $client);

        $this->refresh($client, $tokens['refresh_token'])->assertOk();
    }

    public function test_single_session_off_keeps_every_token_set(): void
    {
        $user = User::factory()->create();
        $client = $this->makeClient();
        $client->policy->update(['single_session' => false]);

        $first = $this->signIn($user, $client);
        $second = $this->signIn($user, $client);

        $this->assertSame(0, DB::table('oauth_access_tokens')->where('revoked', true)->count());
        $this->refresh($client, $first['refresh_token'])->assertOk();
        $this->refresh($client, $second['refresh_token'])->assertOk();
    }

    public function test_deleting_a_required_group_is_blocked(): void
    {
        $group = Group::factory()->create();
        $this->clientRequiring($group);
        $admin = $this->memberOf(Group::factory()->admin()->create());

        $this->actingAs($admin)->delete(route('groups.destroy', $group))
            ->assertSessionHasErrors(['group' => 'Cannot delete: the OAuth client Test Client requires this group.']);

        $this->assertModelExists($group);
    }

    public function test_the_database_refuses_to_delete_a_required_group(): void
    {
        $group = Group::factory()->create();
        $this->clientRequiring($group);

        $this->expectException(QueryException::class);
        $group->delete();
    }

    public function test_deleting_a_client_releases_its_required_groups(): void
    {
        $group = Group::factory()->create();
        $client = $this->clientRequiring($group);
        $admin = $this->memberOf(Group::factory()->admin()->create());

        $this->actingAs($admin)->deleteJson(route('admin.oauth-clients.destroy', $client->getKey()))->assertNoContent();
        $this->actingAs($admin)->delete(route('groups.destroy', $group))->assertSessionHasNoErrors();

        $this->assertModelMissing($group);
    }

    private function clientRequiring(Group ...$groups): Client
    {
        $client = $this->makeClient();
        $client->policy->requiredGroups()->attach(array_map(fn (Group $group) => $group->id, $groups));

        return $client;
    }

    private function memberOf(Group $group): User
    {
        $user = User::factory()->create();
        $group->members()->attach($user->id, ['created_at' => now()]);

        return $user;
    }
}
