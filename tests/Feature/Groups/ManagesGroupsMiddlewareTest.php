<?php
namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Models\GroupManagerRuleByGroup;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagesGroupsMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get('/groups')->assertRedirect('/login');
    }

    public function test_user_with_no_access_gets_403(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/groups')->assertStatus(403);
    }

    public function test_admin_user_can_access_groups(): void
    {
        $user = User::factory()->create();
        $adminGroup = Group::factory()->admin()->create();
        $adminGroup->members()->attach($user->id, ['created_at' => now()]);

        $this->actingAs($user)->get('/groups')->assertStatus(200);
    }

    public function test_group_manager_can_access_groups_index(): void
    {
        $manager = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $targetGroup = Group::factory()->create();
        $managerGroup->members()->attach($manager->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id,
            'target_group_id' => $targetGroup->id,
        ]);

        $this->actingAs($manager)->get('/groups')->assertStatus(200);
    }
}
