<?php
namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Models\GroupAttributeDefinition;
use App\Models\GroupAttributeValue;
use App\Models\GroupTag;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ApiGroupsScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_groups_not_included_without_scope(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['full_name']);

        $this->getJson('/api/user')
            ->assertStatus(200)
            ->assertJsonMissingPath('data.groups');
    }

    public function test_groups_included_with_scope(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['slug' => 'vacc-norway', 'name' => 'vACC Norway']);
        $group->members()->attach($user->id, ['created_at' => now()]);
        Passport::actingAs($user, ['groups']);

        $this->getJson('/api/user')
            ->assertStatus(200)
            ->assertJsonPath('data.groups.0.slug', 'vacc-norway')
            ->assertJsonPath('data.groups.0.name', 'vACC Norway')
            ->assertJsonStructure(['data' => ['groups' => [['id', 'slug', 'name', 'tags', 'attributes']]]]);
    }

    public function test_groups_response_includes_tags_and_attributes(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['slug' => 'test-group']);
        $group->members()->attach($user->id, ['created_at' => now()]);
        GroupTag::create(['group_id' => $group->id, 'tag' => 'vacc']);
        $def = GroupAttributeDefinition::factory()->create(['key' => 'region']);
        GroupAttributeValue::create(['group_id' => $group->id, 'attribute_definition_id' => $def->id, 'value' => 'EUR']);
        Passport::actingAs($user, ['groups']);

        $response = $this->getJson('/api/user')->assertStatus(200);
        $groups = $response->json('data.groups');
        $this->assertEquals(['vacc'], $groups[0]['tags']);
        $this->assertEquals(['region' => 'EUR'], $groups[0]['attributes']);
    }

    public function test_user_with_no_groups_gets_empty_array(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['groups']);

        $this->getJson('/api/user')
            ->assertStatus(200)
            ->assertJsonPath('data.groups', []);
    }
}
