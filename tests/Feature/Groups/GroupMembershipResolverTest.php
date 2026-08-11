<?php
namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Services\GroupMembershipResolver;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GroupMembershipResolverTest extends TestCase
{
    use RefreshDatabase;

    private GroupMembershipResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new GroupMembershipResolver();
    }

    private function nest(Group $child, Group $parent): void
    {
        DB::table('group_hierarchy')->insert(['parent_id' => $parent->id, 'child_id' => $child->id]);
    }

    public function test_direct_only_membership(): void
    {
        $user = User::factory()->create();
        $g = Group::factory()->create();
        $g->members()->attach($user->id, ['created_at' => now()]);

        $map = $this->resolver->effectiveMembership($user);
        $this->assertSame([$g->id => null], $map);
    }

    public function test_user_in_no_groups_is_empty(): void
    {
        $user = User::factory()->create();
        $this->assertSame([], $this->resolver->effectiveMembership($user));
    }

    public function test_rolls_up_a_parent_chain(): void
    {
        $user = User::factory()->create();
        $child = Group::factory()->create();
        $parent = Group::factory()->create();
        $grandparent = Group::factory()->create();
        $this->nest($child, $parent);
        $this->nest($parent, $grandparent);
        $child->members()->attach($user->id, ['created_at' => now()]);

        $map = $this->resolver->effectiveMembership($user);

        $this->assertNull($map[$child->id]);              // direct
        $this->assertSame($child->id, $map[$parent->id]); // via child
        $this->assertSame($parent->id, $map[$grandparent->id]);
    }

    public function test_diamond_dag_reaches_top_once(): void
    {
        $user = User::factory()->create();
        $child = Group::factory()->create();
        $left = Group::factory()->create();
        $right = Group::factory()->create();
        $top = Group::factory()->create();
        $this->nest($child, $left);
        $this->nest($child, $right);
        $this->nest($left, $top);
        $this->nest($right, $top);
        $child->members()->attach($user->id, ['created_at' => now()]);

        $map = $this->resolver->effectiveMembership($user);

        $this->assertArrayHasKey($top->id, $map);
        $this->assertNotNull($map[$top->id]); // inherited, appears exactly once (it's a single map key)
        $this->assertCount(4, $map);
    }

    public function test_direct_membership_wins_over_inherited(): void
    {
        $user = User::factory()->create();
        $child = Group::factory()->create();
        $parent = Group::factory()->create();
        $this->nest($child, $parent);
        $child->members()->attach($user->id, ['created_at' => now()]);
        $parent->members()->attach($user->id, ['created_at' => now()]); // also direct in parent

        $map = $this->resolver->effectiveMembership($user);

        $this->assertNull($map[$parent->id]); // direct wins, not "via child"
    }

    public function test_terminates_on_injected_cycle(): void
    {
        $user = User::factory()->create();
        $a = Group::factory()->create();
        $b = Group::factory()->create();
        $this->nest($a, $b); // a nests into b
        $this->nest($b, $a); // b nests into a  -> cycle, bypasses write-time guard
        $a->members()->attach($user->id, ['created_at' => now()]);

        $map = $this->resolver->effectiveMembership($user);

        $this->assertArrayHasKey($a->id, $map);
        $this->assertArrayHasKey($b->id, $map);
        $this->assertCount(2, $map); // each node once, no infinite loop
    }

    public function test_effective_group_ids_are_the_map_keys(): void
    {
        $user = User::factory()->create();
        $child = Group::factory()->create();
        $parent = Group::factory()->create();
        $this->nest($child, $parent);
        $child->members()->attach($user->id, ['created_at' => now()]);

        $ids = $this->resolver->effectiveGroupIds($user);
        $this->assertEqualsCanonicalizing([$child->id, $parent->id], $ids);
    }
}
