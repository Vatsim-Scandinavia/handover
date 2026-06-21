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
}
