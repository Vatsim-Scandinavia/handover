<?php

declare(strict_types=1);

namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Models\GroupManagerRuleByGroup;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupMemberTest extends TestCase
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

    public function test_admin_can_view_members(): void
    {
        $group = Group::factory()->create();
        $member = User::factory()->create(['first_name' => 'Alice']);
        $group->members()->attach($member->id, ['created_at' => now()]);

        $this->actingAs($this->adminUser())
            ->get(route('groups.members.index', $group))
            ->assertStatus(200)
            ->assertSee('Alice');
    }

    public function test_manager_can_view_managed_group_members(): void
    {
        $manager = User::factory()->create();
        $mg = Group::factory()->create();
        $tg = Group::factory()->create();
        $mg->members()->attach($manager->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create(['manager_group_id' => $mg->id, 'target_group_id' => $tg->id]);

        $this->actingAs($manager)->get(route('groups.members.index', $tg))->assertStatus(200);
    }

    public function test_manager_cannot_view_unmanaged_group_members(): void
    {
        $manager = User::factory()->create();
        $mg = Group::factory()->create();
        $managed = Group::factory()->create();
        $unmanaged = Group::factory()->create();
        $mg->members()->attach($manager->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create(['manager_group_id' => $mg->id, 'target_group_id' => $managed->id]);

        $this->actingAs($manager)->get(route('groups.members.index', $unmanaged))->assertStatus(403);
    }

    public function test_admin_can_add_member(): void
    {
        $group = Group::factory()->create();
        User::factory()->create(['id' => 1234567]);

        $this->actingAs($this->adminUser())
            ->post(route('groups.members.store', $group), ['cid' => 1234567])
            ->assertRedirect();

        $this->assertTrue($group->members()->where('user_id', 1234567)->exists());
    }

    public function test_adding_already_existing_member_is_idempotent(): void
    {
        $group = Group::factory()->create();
        User::factory()->create(['id' => 1234567]);
        $group->members()->attach(1234567, ['created_at' => now()]);

        $this->actingAs($this->adminUser())
            ->post(route('groups.members.store', $group), ['cid' => 1234567])
            ->assertRedirect()
            ->assertSessionHas('info');

        $this->assertEquals(1, $group->members()->where('user_id', 1234567)->count());
    }

    public function test_adding_unknown_cid_returns_404(): void
    {
        $group = Group::factory()->create();
        $this->actingAs($this->adminUser())
            ->post(route('groups.members.store', $group), ['cid' => 9999999])
            ->assertStatus(404);
    }

    public function test_admin_can_remove_member(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create(['id' => 1234567]);
        $group->members()->attach(1234567, ['created_at' => now()]);

        $this->actingAs($this->adminUser())
            ->delete(route('groups.members.destroy', [$group, $user]))
            ->assertRedirect();

        $this->assertFalse($group->members()->where('user_id', 1234567)->exists());
    }

    public function test_removing_non_member_is_idempotent(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create(['id' => 1234567]);

        $this->actingAs($this->adminUser())
            ->delete(route('groups.members.destroy', [$group, $user]))
            ->assertRedirect()
            ->assertSessionHas('info');
    }

    public function test_added_by_pivot_is_populated(): void
    {
        $group = Group::factory()->create();
        User::factory()->create(['id' => 1234567]);
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('groups.members.store', $group), ['cid' => 1234567]);

        $pivot = $group->members()->where('user_id', 1234567)->first()->pivot;
        $this->assertEquals($admin->id, $pivot->added_by);
    }

    public function test_cannot_remove_last_member_of_admin_group(): void
    {
        $adminGroup = Group::factory()->admin()->create();
        $user = User::factory()->create();
        $adminGroup->members()->attach($user->id, ['created_at' => now()]);

        $this->actingAs($this->adminUser())
            ->delete(route('groups.members.destroy', [$adminGroup, $user]))
            ->assertSessionHasErrors('member');

        $this->assertTrue($adminGroup->members()->where('user_id', $user->id)->exists());
    }

    public function test_member_search_by_name(): void
    {
        $group = Group::factory()->create();
        $zara = User::factory()->create(['first_name' => 'Zara', 'last_name' => 'Smith', 'id' => 1111111]);
        $bob = User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Jones', 'id' => 2222222]);
        $group->members()->attach($zara->id, ['created_at' => now()]);
        $group->members()->attach($bob->id, ['created_at' => now()]);

        $this->actingAs($this->adminUser())
            ->get(route('groups.members.index', $group).'?search=Zar')
            ->assertSee('<td>Zara Smith</td>', false)
            ->assertDontSee('<td>Bob Jones</td>', false);
    }

    public function test_member_search_matches_full_name(): void
    {
        $group = Group::factory()->create();
        $zara = User::factory()->create(['first_name' => 'Zara', 'last_name' => 'Smith']);
        $zack = User::factory()->create(['first_name' => 'Zara', 'last_name' => 'Jones']);
        $group->members()->attach([$zara->id, $zack->id], ['created_at' => now()]);

        $this->actingAs($this->adminUser())
            ->get(route('groups.members.index', ['group' => $group, 'search' => 'zara smi']))
            ->assertSee('<td>Zara Smith</td>', false)
            ->assertDontSee('<td>Zara Jones</td>', false);
    }

    public function test_candidates_match_name_and_cid_and_flag_members(): void
    {
        $group = Group::factory()->create();
        $member = User::factory()->create(['id' => 1234567, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $other = User::factory()->create(['id' => 7654321, 'first_name' => 'Ada', 'last_name' => 'Byron']);
        User::factory()->create(['id' => 5555555, 'first_name' => 'Grace', 'last_name' => 'Hopper']);
        $group->members()->attach($member->id, ['created_at' => now()]);
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->getJson(route('groups.members.candidates', ['group' => $group, 'q' => 'ada']))
            ->assertOk()
            ->assertExactJson([
                ['id' => $other->id, 'name' => 'Ada Byron', 'detail' => '7654321', 'disabled' => false],
                ['id' => $member->id, 'name' => 'Ada Lovelace', 'detail' => '1234567', 'disabled' => true],
            ]);

        $this->actingAs($admin)
            ->getJson(route('groups.members.candidates', ['group' => $group, 'q' => '76543']))
            ->assertExactJson([['id' => $other->id, 'name' => 'Ada Byron', 'detail' => '7654321', 'disabled' => false]]);
    }

    public function test_candidates_need_two_characters(): void
    {
        User::factory()->create(['first_name' => 'Ada']);

        $this->actingAs($this->adminUser())
            ->getJson(route('groups.members.candidates', ['group' => Group::factory()->create(), 'q' => 'a']))
            ->assertExactJson([]);
    }

    public function test_candidates_need_access_to_the_group(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('groups.members.candidates', ['group' => Group::factory()->create(), 'q' => 'ada']))
            ->assertForbidden();
    }
}
