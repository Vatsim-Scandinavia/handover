<?php
namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Models\GroupAttributeDefinition;
use App\Models\GroupAttributeValue;
use App\Models\GroupManagerRuleByAttribute;
use App\Models\GroupManagerRuleByGroup;
use App\Models\GroupManagerRuleByTag;
use App\Models\GroupTag;
use App\Services\GroupManagerService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GroupManagerServiceTest extends TestCase
{
    use RefreshDatabase;

    private GroupManagerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GroupManagerService();
    }

    public function test_admin_user_is_detected(): void
    {
        $admin = User::factory()->create();
        $adminGroup = Group::factory()->admin()->create();
        $adminGroup->members()->attach($admin->id, ['created_at' => now()]);

        $this->assertTrue($this->service->isAdmin($admin));
    }

    public function test_non_admin_user_is_not_detected_as_admin(): void
    {
        $user = User::factory()->create();
        Group::factory()->create(); // non-admin group, user not a member
        $this->assertFalse($this->service->isAdmin($user));
    }

    public function test_can_manage_via_specific_group_rule(): void
    {
        $manager = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();
        $managerGroup->members()->attach($manager->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id,
            'target_group_id' => $targetGroup->id,
        ]);

        $this->assertTrue($this->service->canManage($manager, $targetGroup));
    }

    public function test_can_manage_via_tag_rule(): void
    {
        $manager = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();
        $managerGroup->members()->attach($manager->id, ['created_at' => now()]);
        GroupTag::create(['group_id' => $targetGroup->id, 'tag' => 'vacc']);
        GroupManagerRuleByTag::create([
            'manager_group_id' => $managerGroup->id,
            'target_tag' => 'vacc',
        ]);

        $this->assertTrue($this->service->canManage($manager, $targetGroup));
    }

    public function test_can_manage_via_attribute_rule(): void
    {
        $manager = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();
        $def = GroupAttributeDefinition::factory()->create(['key' => 'region']);
        $managerGroup->members()->attach($manager->id, ['created_at' => now()]);
        GroupAttributeValue::create([
            'group_id' => $targetGroup->id,
            'attribute_definition_id' => $def->id,
            'value' => 'EUR',
        ]);
        GroupManagerRuleByAttribute::create([
            'manager_group_id' => $managerGroup->id,
            'target_attribute_key' => 'region',
            'target_attribute_value' => 'EUR',
        ]);

        $this->assertTrue($this->service->canManage($manager, $targetGroup));
    }

    public function test_cannot_manage_without_matching_rule(): void
    {
        $manager = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();
        $managerGroup->members()->attach($manager->id, ['created_at' => now()]);
        // no rules created

        $this->assertFalse($this->service->canManage($manager, $targetGroup));
    }

    public function test_admin_can_manage_any_group(): void
    {
        $admin = User::factory()->create();
        $adminGroup = Group::factory()->admin()->create();
        $adminGroup->members()->attach($admin->id, ['created_at' => now()]);
        $targetGroup = Group::factory()->create();

        $this->assertTrue($this->service->canManage($admin, $targetGroup));
    }

    public function test_manageable_group_ids_are_cached(): void
    {
        Cache::flush();
        $manager = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();
        $managerGroup->members()->attach($manager->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id,
            'target_group_id' => $targetGroup->id,
        ]);

        $version = Cache::get('groups:cache_version', 0);
        $this->service->manageableGroupIds($manager);
        $this->assertTrue(Cache::has("user:{$manager->id}:manageable_groups:v{$version}"));
    }

    public function test_increment_cache_version_invalidates_results(): void
    {
        Cache::flush();
        $manager = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();
        $managerGroup->members()->attach($manager->id, ['created_at' => now()]);

        $ids = $this->service->manageableGroupIds($manager);
        $this->assertEmpty($ids);

        GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id,
            'target_group_id' => $targetGroup->id,
        ]);
        $this->service->incrementCacheVersion();

        $ids = $this->service->manageableGroupIds($manager);
        $this->assertContains($targetGroup->id, $ids);
    }

    public function test_inherited_membership_grants_management_rights(): void
    {
        $user = User::factory()->create();
        $childGroup = Group::factory()->create();
        $managerGroup = Group::factory()->create(); // parent of childGroup
        $targetGroup = Group::factory()->create();

        DB::table('group_hierarchy')->insert([
            'parent_id' => $managerGroup->id,
            'child_id'  => $childGroup->id,
        ]);
        $childGroup->members()->attach($user->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id, // rule attached to the PARENT
            'target_group_id'  => $targetGroup->id,
        ]);

        // User is only a direct member of the child, but inherits managerGroup.
        $this->assertTrue($this->service->canManage($user, $targetGroup));
    }

    public function test_granting_rules_note_inherited_path(): void
    {
        $user = User::factory()->create();
        $childGroup = Group::factory()->create(['name' => 'vACC Norway']);
        $managerGroup = Group::factory()->create(['name' => 'vACC Directors']);
        $targetGroup = Group::factory()->create();

        DB::table('group_hierarchy')->insert([
            'parent_id' => $managerGroup->id,
            'child_id'  => $childGroup->id,
        ]);
        $childGroup->members()->attach($user->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id,
            'target_group_id'  => $targetGroup->id,
        ]);

        $rules = $this->service->grantingRulesFor($user, $targetGroup);
        $this->assertStringContainsString('vACC Directors', $rules[0]);
        $this->assertStringContainsString('inherited via "vACC Norway"', $rules[0]);
    }

    public function test_nesting_under_admin_group_does_not_grant_admin(): void
    {
        $user = User::factory()->create();
        $childGroup = Group::factory()->create();
        $adminGroup = Group::factory()->admin()->create();

        DB::table('group_hierarchy')->insert([
            'parent_id' => $adminGroup->id,
            'child_id'  => $childGroup->id,
        ]);
        $childGroup->members()->attach($user->id, ['created_at' => now()]);

        // Effective membership includes the admin group, but isAdmin stays direct-only.
        $this->assertFalse($this->service->isAdmin($user));
    }

    public function test_rule_target_does_not_expand_to_nested_children(): void
    {
        // A by-group rule targets a parent group. Nesting must NOT extend the rule's
        // target set to the parent's children — only the MANAGER side expands.
        $manager = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $parent = Group::factory()->create();
        $child = Group::factory()->create();
        $managerGroup->members()->attach($manager->id, ['created_at' => now()]);
        DB::table('group_hierarchy')->insert(['parent_id' => $parent->id, 'child_id' => $child->id]);
        GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id,
            'target_group_id'  => $parent->id,
        ]);

        $this->assertTrue($this->service->canManage($manager, $parent));  // the target itself
        $this->assertFalse($this->service->canManage($manager, $child));  // NOT its nested child
    }
}
