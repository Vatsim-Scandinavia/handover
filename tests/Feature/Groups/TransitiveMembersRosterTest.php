<?php
namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Models\GroupManagerRuleByGroup;
use App\Services\GroupMembershipResolver;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransitiveMembersRosterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        Group::factory()->admin()->create()->members()->attach($admin->id, ['created_at' => now()]);
        return $admin;
    }

    private function nest(Group $child, Group $parent): void
    {
        DB::table('group_hierarchy')->insert(['parent_id' => $parent->id, 'child_id' => $child->id]);
    }

    private function enrol(Group $group, User $user): User
    {
        $group->members()->attach($user->id, ['created_at' => now()]);
        return $user;
    }

    private function transitiveMembers(Group $group): array
    {
        return app(GroupMembershipResolver::class)->transitiveMembers($group);
    }

    // ── Resolver traversal ───────────────────────────────────────────────

    public function test_resolver_collects_members_down_a_three_level_chain(): void
    {
        // bottom → middle → top (each nests into the next).
        [$top, $middle, $bottom] = [Group::factory()->create(), Group::factory()->create(), Group::factory()->create()];
        $this->nest($middle, $top);
        $this->nest($bottom, $middle);

        $deep = $this->enrol($bottom, User::factory()->create());
        $mid = $this->enrol($middle, User::factory()->create());

        $map = $this->transitiveMembers($top);

        // Both roll up to the top group, each via the group they enrol in.
        $this->assertSame([$bottom->id], $map[$deep->id]);
        $this->assertSame([$middle->id], $map[$mid->id]);
    }

    public function test_member_reachable_by_two_paths_appears_once(): void
    {
        // Diamond: bottom nests into both left and right, both nest into top.
        [$top, $left, $right, $bottom] = Group::factory()->count(4)->create()->all();
        $this->nest($left, $top);
        $this->nest($right, $top);
        $this->nest($bottom, $left);
        $this->nest($bottom, $right);

        $user = $this->enrol($bottom, User::factory()->create());

        // Enrolled only in bottom → a single source, no duplication from the two paths.
        $this->assertSame([$bottom->id], $this->transitiveMembers($top)[$user->id]);
    }

    public function test_direct_membership_is_recorded_alongside_inherited(): void
    {
        $group = Group::factory()->create();
        $child = Group::factory()->create();
        $this->nest($child, $group);

        $user = User::factory()->create();
        $this->enrol($group, $user); // direct
        $this->enrol($child, $user); // also inherited

        $sources = $this->transitiveMembers($group)[$user->id];
        $this->assertContains($group->id, $sources);
        $this->assertContains($child->id, $sources);
    }

    // ── Page ─────────────────────────────────────────────────────────────

    public function test_all_members_page_shows_direct_and_inherited_with_source(): void
    {
        $group = Group::factory()->create();
        $child = Group::factory()->create(['name' => 'Norway Trainees']);
        $this->nest($child, $group);

        $this->enrol($group, User::factory()->create(['first_name' => 'Ada']));
        $this->enrol($child, User::factory()->create(['first_name' => 'Bo']));

        $this->actingAs($this->admin())
            ->get(route('groups.members.all', $group))
            ->assertOk()
            ->assertSee('Ada')                                          // direct member listed
            ->assertSee('Bo')                                           // inherited member listed
            ->assertSee('<span class="badge bg-success">Direct</span>', false)  // direct source badge
            ->assertSee('via Norway Trainees')                          // inherited source badge
            ->assertDontSee('Add Member')                               // read-only: no add form
            ->assertDontSee('Remove');                                  // read-only: no per-row remove
    }

    public function test_all_members_page_lists_a_diamond_member_once(): void
    {
        $group = Group::factory()->create();
        $left = Group::factory()->create();
        $right = Group::factory()->create();
        $this->nest($left, $group);
        $this->nest($right, $group);

        $user = User::factory()->create(['first_name' => 'Solo', 'last_name' => 'Once']);
        $this->enrol($left, $user);
        $this->enrol($right, $user);

        $html = $this->actingAs($this->admin())
            ->get(route('groups.members.all', $group))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, '<td>Solo Once</td>'));
    }

    public function test_direct_members_page_still_excludes_inherited(): void
    {
        $group = Group::factory()->create();
        $child = Group::factory()->create();
        $this->nest($child, $group);

        $this->enrol($group, User::factory()->create(['first_name' => 'Ada']));
        $this->enrol($child, User::factory()->create(['first_name' => 'Bo']));

        $this->actingAs($this->admin())
            ->get(route('groups.members.index', $group))
            ->assertOk()
            ->assertSee('Ada')
            ->assertDontSee('Bo')        // inherited member not on the direct view
            ->assertSee('Add Member');   // direct view keeps management controls
    }

    public function test_all_members_search_filters_the_roster(): void
    {
        $group = Group::factory()->create();
        $child = Group::factory()->create();
        $this->nest($child, $group);

        $this->enrol($group, User::factory()->create(['first_name' => 'Ada']));
        $this->enrol($child, User::factory()->create(['first_name' => 'Zizzy']));

        $this->actingAs($this->admin())
            ->get(route('groups.members.all', ['group' => $group, 'search' => 'Ada']))
            ->assertOk()
            ->assertSee('Ada')
            ->assertDontSee('Zizzy');
    }

    public function test_manager_can_view_all_members(): void
    {
        $manager = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $group = Group::factory()->create();
        $this->enrol($managerGroup, $manager);
        GroupManagerRuleByGroup::create(['manager_group_id' => $managerGroup->id, 'target_group_id' => $group->id]);

        $this->actingAs($manager)
            ->get(route('groups.members.all', $group))
            ->assertOk();
    }

    public function test_user_without_access_cannot_view_all_members(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('groups.members.all', Group::factory()->create()))
            ->assertForbidden();
    }
}
