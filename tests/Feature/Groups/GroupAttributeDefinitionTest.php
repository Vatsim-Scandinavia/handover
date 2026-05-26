<?php
namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Models\GroupAttributeDefinition;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupAttributeDefinitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $adminGroup = Group::factory()->admin()->create();
        $adminGroup->members()->attach($user->id, ['created_at' => now()]);
        return $user;
    }

    public function test_non_admin_cannot_access_attribute_definitions(): void
    {
        $user = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();
        $managerGroup->members()->attach($user->id, ['created_at' => now()]);
        \App\Models\GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id,
            'target_group_id' => $targetGroup->id,
        ]);

        $this->actingAs($user)->get(route('groups.attributes.index'))->assertStatus(403);
    }

    public function test_admin_can_list_attribute_definitions(): void
    {
        GroupAttributeDefinition::factory()->create(['key' => 'region', 'label' => 'Region']);
        $this->actingAs($this->adminUser())
            ->get(route('groups.attributes.index'))
            ->assertStatus(200)
            ->assertSee('Region');
    }

    public function test_admin_can_create_attribute_definition(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('groups.attributes.store'), ['key' => 'division', 'label' => 'Division'])
            ->assertRedirect(route('groups.attributes.index'));

        $this->assertDatabaseHas('group_attribute_definitions', ['key' => 'division']);
    }

    public function test_key_must_match_allowed_pattern(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('groups.attributes.store'), ['key' => 'INVALID KEY!', 'label' => 'Test'])
            ->assertSessionHasErrors('key');
    }

    public function test_admin_can_delete_unused_attribute_definition(): void
    {
        $def = GroupAttributeDefinition::factory()->create();
        $this->actingAs($this->adminUser())
            ->delete(route('groups.attributes.destroy', $def))
            ->assertRedirect(route('groups.attributes.index'));

        $this->assertDatabaseMissing('group_attribute_definitions', ['id' => $def->id]);
    }

    public function test_admin_can_update_attribute_label(): void
    {
        $def = GroupAttributeDefinition::factory()->create(['key' => 'region', 'label' => 'Region']);
        $this->actingAs($this->adminUser())
            ->patch(route('groups.attributes.update', $def), ['label' => 'Geographic Region'])
            ->assertRedirect(route('groups.attributes.index'));

        $this->assertDatabaseHas('group_attribute_definitions', ['id' => $def->id, 'label' => 'Geographic Region', 'key' => 'region']);
    }

    public function test_update_label_cannot_be_empty(): void
    {
        $def = GroupAttributeDefinition::factory()->create(['label' => 'Region']);
        $this->actingAs($this->adminUser())
            ->patch(route('groups.attributes.update', $def), ['label' => ''])
            ->assertSessionHasErrors('label');
    }

    public function test_non_admin_cannot_update_attribute_definition(): void
    {
        $user = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();
        $managerGroup->members()->attach($user->id, ['created_at' => now()]);
        \App\Models\GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id,
            'target_group_id' => $targetGroup->id,
        ]);

        $def = GroupAttributeDefinition::factory()->create(['label' => 'Original']);
        $this->actingAs($user)
            ->patch(route('groups.attributes.update', $def), ['label' => 'Changed'])
            ->assertStatus(403);

        $this->assertDatabaseHas('group_attribute_definitions', ['id' => $def->id, 'label' => 'Original']);
    }

    public function test_cannot_delete_definition_with_existing_values(): void
    {
        $admin = $this->adminUser();
        $def = GroupAttributeDefinition::factory()->create();
        $group = Group::factory()->create();
        \App\Models\GroupAttributeValue::create([
            'group_id' => $group->id,
            'attribute_definition_id' => $def->id,
            'value' => 'test',
        ]);

        $this->actingAs($admin)
            ->delete(route('groups.attributes.destroy', $def))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('group_attribute_definitions', ['id' => $def->id]);
    }
}
