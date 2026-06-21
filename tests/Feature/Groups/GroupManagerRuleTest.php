<?php
namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Models\GroupAttributeDefinition;
use App\Models\GroupManagerRuleByAttribute;
use App\Models\GroupManagerRuleByGroup;
use App\Models\GroupManagerRuleByTag;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupManagerRuleTest extends TestCase
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
        Group::factory()->admin()->create()->members()->attach($user->id, ['created_at' => now()]);
        return $user;
    }

    public function test_non_admin_cannot_access_rules_page(): void
    {
        $manager = User::factory()->create();
        $mg = Group::factory()->create();
        $tg = Group::factory()->create();
        $mg->members()->attach($manager->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create(['manager_group_id' => $mg->id, 'target_group_id' => $tg->id]);

        $this->actingAs($manager)->get(route('groups.rules.index', $tg))->assertStatus(403);
    }

    public function test_admin_can_view_rules_page(): void
    {
        $group = Group::factory()->create();
        $this->actingAs($this->adminUser())
            ->get(route('groups.rules.index', $group))
            ->assertStatus(200);
    }

    public function test_can_add_group_type_rule(): void
    {
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();

        $this->actingAs($this->adminUser())
            ->post(route('groups.rules.store', $targetGroup), [
                'type'             => 'group',
                'manager_group_id' => $managerGroup->id,
                'target_group_id'  => $managerGroup->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('group_manager_rules_by_group', [
            'manager_group_id' => $managerGroup->id,
            'target_group_id'  => $targetGroup->id,
        ]);
    }

    public function test_can_add_tag_type_rule(): void
    {
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();

        $this->actingAs($this->adminUser())
            ->post(route('groups.rules.store', $targetGroup), [
                'type'             => 'tag',
                'manager_group_id' => $managerGroup->id,
                'target_tag'       => 'vacc',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('group_manager_rules_by_tag', [
            'manager_group_id' => $managerGroup->id,
            'target_tag'       => 'vacc',
        ]);
    }

    public function test_can_add_attribute_type_rule(): void
    {
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();
        GroupAttributeDefinition::factory()->create(['key' => 'region']);

        $this->actingAs($this->adminUser())
            ->post(route('groups.rules.store', $targetGroup), [
                'type'                   => 'attribute',
                'manager_group_id'       => $managerGroup->id,
                'target_attribute_key'   => 'region',
                'target_attribute_value' => 'EUR',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('group_manager_rules_by_attribute', [
            'manager_group_id'       => $managerGroup->id,
            'target_attribute_key'   => 'region',
            'target_attribute_value' => 'EUR',
        ]);
    }

    public function test_can_delete_group_type_rule(): void
    {
        $rule = GroupManagerRuleByGroup::create([
            'manager_group_id' => Group::factory()->create()->id,
            'target_group_id'  => Group::factory()->create()->id,
        ]);
        $target = Group::find($rule->target_group_id);

        $this->actingAs($this->adminUser())
            ->delete(route('groups.rules.destroy', [$target, 'group', $rule->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('group_manager_rules_by_group', ['id' => $rule->id]);
    }

    public function test_can_delete_tag_type_rule(): void
    {
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();
        $targetGroup->tags()->create(['tag' => 'vacc']);
        $rule = GroupManagerRuleByTag::create([
            'manager_group_id' => $managerGroup->id,
            'target_tag'       => 'vacc',
        ]);

        $this->actingAs($this->adminUser())
            ->delete(route('groups.rules.destroy', [$targetGroup, 'tag', $rule->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('group_manager_rules_by_tag', ['id' => $rule->id]);
    }

    public function test_can_delete_attribute_type_rule(): void
    {
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();
        $def = GroupAttributeDefinition::factory()->create(['key' => 'region']);
        $targetGroup->attributeValues()->create(['attribute_definition_id' => $def->id, 'value' => 'EUR']);
        $rule = GroupManagerRuleByAttribute::create([
            'manager_group_id'       => $managerGroup->id,
            'target_attribute_key'   => 'region',
            'target_attribute_value' => 'EUR',
        ]);

        $this->actingAs($this->adminUser())
            ->delete(route('groups.rules.destroy', [$targetGroup, 'attribute', $rule->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('group_manager_rules_by_attribute', ['id' => $rule->id]);
    }

    public function test_overview_can_delete_group_type_rule(): void
    {
        $rule = GroupManagerRuleByGroup::create([
            'manager_group_id' => Group::factory()->create()->id,
            'target_group_id'  => Group::factory()->create()->id,
        ]);

        $this->actingAs($this->adminUser())
            ->delete(route('groups.rules.overview.destroy', ['group', $rule->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('group_manager_rules_by_group', ['id' => $rule->id]);
    }

    public function test_overview_can_delete_tag_type_rule(): void
    {
        $rule = GroupManagerRuleByTag::create([
            'manager_group_id' => Group::factory()->create()->id,
            'target_tag'       => 'vacc',
        ]);

        $this->actingAs($this->adminUser())
            ->delete(route('groups.rules.overview.destroy', ['tag', $rule->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('group_manager_rules_by_tag', ['id' => $rule->id]);
    }

    public function test_overview_can_delete_attribute_type_rule(): void
    {
        GroupAttributeDefinition::factory()->create(['key' => 'region']);
        $rule = GroupManagerRuleByAttribute::create([
            'manager_group_id'       => Group::factory()->create()->id,
            'target_attribute_key'   => 'region',
            'target_attribute_value' => 'EUR',
        ]);

        $this->actingAs($this->adminUser())
            ->delete(route('groups.rules.overview.destroy', ['attribute', $rule->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('group_manager_rules_by_attribute', ['id' => $rule->id]);
    }

    public function test_overview_non_admin_cannot_delete_rule(): void
    {
        $manager = User::factory()->create();
        $mg = Group::factory()->create();
        $tg = Group::factory()->create();
        $mg->members()->attach($manager->id, ['created_at' => now()]);
        $rule = GroupManagerRuleByGroup::create(['manager_group_id' => $mg->id, 'target_group_id' => $tg->id]);

        $this->actingAs($manager)
            ->delete(route('groups.rules.overview.destroy', ['group', $rule->id]))
            ->assertStatus(403);

        $this->assertDatabaseHas('group_manager_rules_by_group', ['id' => $rule->id]);
    }

    public function test_cannot_delete_tag_rule_for_unrelated_group(): void
    {
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();
        $targetGroup->tags()->create(['tag' => 'vacc']);
        $rule = GroupManagerRuleByTag::create([
            'manager_group_id' => $managerGroup->id,
            'target_tag'       => 'unrelated-tag',
        ]);

        $this->actingAs($this->adminUser())
            ->delete(route('groups.rules.destroy', [$targetGroup, 'tag', $rule->id]))
            ->assertStatus(404);

        $this->assertDatabaseHas('group_manager_rules_by_tag', ['id' => $rule->id]);
    }

    public function test_admin_can_view_overview_page(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('groups.rules.overview'))
            ->assertStatus(200);
    }

    public function test_non_admin_group_manager_cannot_view_overview_page(): void
    {
        $manager = User::factory()->create();
        $mg = Group::factory()->create();
        $tg = Group::factory()->create();
        $mg->members()->attach($manager->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create(['manager_group_id' => $mg->id, 'target_group_id' => $tg->id]);

        $this->actingAs($manager)
            ->get(route('groups.rules.overview'))
            ->assertStatus(403);
    }

    public function test_overview_omits_rules_with_deleted_manager_group(): void
    {
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();
        GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id,
            'target_group_id'  => $targetGroup->id,
        ]);

        \DB::table('groups')->where('id', $managerGroup->id)->delete();

        $this->actingAs($this->adminUser())
            ->get(route('groups.rules.overview'))
            ->assertStatus(200);
    }
}
