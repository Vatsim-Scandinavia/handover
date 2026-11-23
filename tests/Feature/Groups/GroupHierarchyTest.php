<?php

declare(strict_types=1);

namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Models\GroupManagerRuleByGroup;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Tests\TestCase;

class GroupHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        Group::factory()->admin()->create()->members()->attach($admin->id, ['created_at' => now()]);

        return $admin;
    }

    private function edgeExists(Group $child, Group $parent): bool
    {
        return DB::table('group_hierarchy')
            ->where('parent_id', $parent->id)->where('child_id', $child->id)->exists();
    }

    public function test_admin_can_nest_a_group_into_a_parent(): void
    {
        $child = Group::factory()->create();
        $parent = Group::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('groups.parents.store', $child), ['parent_id' => $parent->id])
            ->assertRedirect();

        $this->assertTrue($this->edgeExists($child, $parent));
    }

    public function test_self_edge_is_rejected(): void
    {
        $g = Group::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('groups.parents.store', $g), ['parent_id' => $g->id])
            ->assertSessionHasErrors('parent_id');

        $this->assertFalse($this->edgeExists($g, $g));
    }

    public function test_two_node_cycle_is_rejected(): void
    {
        $a = Group::factory()->create();
        $b = Group::factory()->create();
        DB::table('group_hierarchy')->insert(['parent_id' => $a->id, 'child_id' => $b->id]); // b nests into a

        // Now try to nest a into b -> would close a cycle.
        $this->actingAs($this->admin())
            ->post(route('groups.parents.store', $a), ['parent_id' => $b->id])
            ->assertSessionHasErrors('parent_id');

        $this->assertFalse($this->edgeExists($a, $b));
    }

    public function test_longer_cycle_is_rejected(): void
    {
        $a = Group::factory()->create();
        $b = Group::factory()->create();
        $c = Group::factory()->create();
        DB::table('group_hierarchy')->insert(['parent_id' => $a->id, 'child_id' => $b->id]); // b -> a
        DB::table('group_hierarchy')->insert(['parent_id' => $b->id, 'child_id' => $c->id]); // c -> b

        // Nest a into c would make a reachable from c upward -> cycle.
        $this->actingAs($this->admin())
            ->post(route('groups.parents.store', $a), ['parent_id' => $c->id])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_diamond_edge_is_allowed(): void
    {
        $child = Group::factory()->create();
        $left = Group::factory()->create();
        $right = Group::factory()->create();
        DB::table('group_hierarchy')->insert(['parent_id' => $left->id, 'child_id' => $child->id]);

        // child also nests into right -> valid diamond, no cycle.
        $this->actingAs($this->admin())
            ->post(route('groups.parents.store', $child), ['parent_id' => $right->id])
            ->assertRedirect();

        $this->assertTrue($this->edgeExists($child, $right));
    }

    public function test_adding_existing_edge_is_idempotent(): void
    {
        $child = Group::factory()->create();
        $parent = Group::factory()->create();
        DB::table('group_hierarchy')->insert(['parent_id' => $parent->id, 'child_id' => $child->id]);

        $this->actingAs($this->admin())
            ->post(route('groups.parents.store', $child), ['parent_id' => $parent->id])
            ->assertRedirect();

        $this->assertSame(1, DB::table('group_hierarchy')
            ->where('parent_id', $parent->id)->where('child_id', $child->id)->count());
    }

    public function test_admin_can_remove_a_nesting_edge(): void
    {
        $child = Group::factory()->create();
        $parent = Group::factory()->create();
        DB::table('group_hierarchy')->insert(['parent_id' => $parent->id, 'child_id' => $child->id]);

        $this->actingAs($this->admin())
            ->delete(route('groups.parents.destroy', ['group' => $child, 'parent' => $parent->id]))
            ->assertRedirect();

        $this->assertFalse($this->edgeExists($child, $parent));
    }

    public function test_non_admin_manager_cannot_edit_hierarchy(): void
    {
        // A manager (rule-based access) but not an admin.
        $manager = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $child = Group::factory()->create();
        $parent = Group::factory()->create();
        $managerGroup->members()->attach($manager->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id,
            'target_group_id' => $child->id,
        ]);

        $this->actingAs($manager)
            ->post(route('groups.parents.store', $child), ['parent_id' => $parent->id])
            ->assertForbidden();
    }

    public function test_edit_route_no_longer_resolves(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('groups.edit', Group::factory()->create());
    }

    public function test_members_page_lists_member_groups_and_member_of(): void
    {
        $group = Group::factory()->create(['name' => 'vACC Norway']);
        $parent = Group::factory()->create(['name' => 'EUR Region']);
        $child = Group::factory()->create(['name' => 'Norway Trainees']);
        DB::table('group_hierarchy')->insert(['parent_id' => $parent->id, 'child_id' => $group->id]);
        DB::table('group_hierarchy')->insert(['parent_id' => $group->id, 'child_id' => $child->id]);

        $this->actingAs($this->admin())
            ->get(route('groups.members.index', $group))
            ->assertOk()
            ->assertSee('EUR Region')
            ->assertSee('Norway Trainees');
    }

    public function test_members_page_shows_member_of_control_and_admin_hint(): void
    {
        $group = Group::factory()->create();
        Group::factory()->admin()->create(['name' => 'System Administrators']);

        $this->actingAs($this->admin())
            ->get(route('groups.members.index', $group))
            ->assertOk()
            ->assertSee(route('groups.parents.store', $group))
            ->assertSee('does not grant admin'); // hint text near admin-group options
    }

    public function test_admin_can_nest_a_child_group(): void
    {
        $group = Group::factory()->create();
        $child = Group::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('groups.children.store', $group), ['child_id' => $child->id])
            ->assertRedirect();

        // Edge is parent=$group, child=$child.
        $this->assertTrue($this->edgeExists($child, $group));
    }

    public function test_child_self_edge_is_rejected(): void
    {
        $g = Group::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('groups.children.store', $g), ['child_id' => $g->id])
            ->assertSessionHasErrors('child_id');

        $this->assertFalse($this->edgeExists($g, $g));
    }

    public function test_child_cycle_is_rejected(): void
    {
        $group = Group::factory()->create();
        $other = Group::factory()->create();
        // $group nests into $other  (parent=$other, child=$group).
        DB::table('group_hierarchy')->insert(['parent_id' => $other->id, 'child_id' => $group->id]);

        // Adding $other as a child of $group would close a cycle.
        $this->actingAs($this->admin())
            ->post(route('groups.children.store', $group), ['child_id' => $other->id])
            ->assertSessionHasErrors('child_id');

        $this->assertFalse($this->edgeExists($other, $group));
    }

    public function test_admin_can_remove_a_child_edge(): void
    {
        $group = Group::factory()->create();
        $child = Group::factory()->create();
        DB::table('group_hierarchy')->insert(['parent_id' => $group->id, 'child_id' => $child->id]);

        $this->actingAs($this->admin())
            ->delete(route('groups.children.destroy', ['group' => $group, 'child' => $child->id]))
            ->assertRedirect();

        $this->assertFalse($this->edgeExists($child, $group));
    }

    public function test_non_admin_manager_cannot_edit_children(): void
    {
        $manager = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $group = Group::factory()->create();
        $child = Group::factory()->create();
        $managerGroup->members()->attach($manager->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id,
            'target_group_id' => $group->id,
        ]);

        $this->actingAs($manager)
            ->post(route('groups.children.store', $group), ['child_id' => $child->id])
            ->assertForbidden();
    }

    public function test_non_admin_manager_cannot_remove_a_child(): void
    {
        $manager = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $group = Group::factory()->create();
        $child = Group::factory()->create();
        $managerGroup->members()->attach($manager->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id,
            'target_group_id' => $group->id,
        ]);
        DB::table('group_hierarchy')->insert(['parent_id' => $group->id, 'child_id' => $child->id]);

        $this->actingAs($manager)
            ->delete(route('groups.children.destroy', ['group' => $group, 'child' => $child->id]))
            ->assertForbidden();

        // Edge must still exist — the forbidden request must not have deleted it.
        $this->assertTrue($this->edgeExists($child, $group));
    }

    public function test_admin_sees_editing_controls_on_group_page(): void
    {
        $group = Group::factory()->create();
        Group::factory()->admin()->create(['name' => 'System Administrators']);
        $parent = Group::factory()->create();
        $child = Group::factory()->create();
        DB::table('group_hierarchy')->insert(['parent_id' => $parent->id, 'child_id' => $group->id]);
        DB::table('group_hierarchy')->insert(['parent_id' => $group->id, 'child_id' => $child->id]);

        $this->actingAs($this->admin())
            ->get(route('groups.show', $group))
            ->assertOk()
            ->assertSee('Save changes')          // core-field form
            ->assertSee('Delete group')          // danger zone
            ->assertSee('x-data="{ editing:', false)     // Alpine edit toggle wired
            ->assertSee('editing = !editing', false)     // toggle button flips edit mode
            ->assertSee(':disabled="!editing"', false);  // controls disabled until editing, not hidden
    }

    public function test_admin_sees_nesting_controls_on_members_page(): void
    {
        $group = Group::factory()->create();
        Group::factory()->admin()->create(['name' => 'System Administrators']);
        $parent = Group::factory()->create();
        $child = Group::factory()->create();
        DB::table('group_hierarchy')->insert(['parent_id' => $parent->id, 'child_id' => $group->id]);
        DB::table('group_hierarchy')->insert(['parent_id' => $group->id, 'child_id' => $child->id]);

        $this->actingAs($this->admin())
            ->get(route('groups.members.index', $group))
            ->assertOk()
            ->assertSee(route('groups.parents.store', $group))     // member-of add control
            ->assertSee(route('groups.children.store', $group))    // member-group add control
            ->assertSee(route('groups.parents.destroy', ['group' => $group, 'parent' => $parent->id]))   // per-row remove
            ->assertSee(route('groups.children.destroy', ['group' => $group, 'child' => $child->id]))
            ->assertSee('data-admin="1"', false)         // admin-group options flagged for the dynamic warning
            ->assertSee('parentIsAdmin', false)          // warning shown reactively on selecting an admin parent
            ->assertDontSee('(admin group');             // no static suffix in the dropdown options
    }

    public function test_manager_sees_read_only_group_page(): void
    {
        $manager = User::factory()->create();
        $managerGroup = Group::factory()->create();
        $group = Group::factory()->create(['description' => 'A read-only overview']);
        $parent = Group::factory()->create();
        $child = Group::factory()->create();
        DB::table('group_hierarchy')->insert(['parent_id' => $parent->id, 'child_id' => $group->id]);
        DB::table('group_hierarchy')->insert(['parent_id' => $group->id, 'child_id' => $child->id]);
        $managerGroup->members()->attach($manager->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id,
            'target_group_id' => $group->id,
        ]);

        $this->actingAs($manager)
            ->get(route('groups.show', $group))
            ->assertOk()
            ->assertSee('A read-only overview')  // overview renders
            ->assertDontSee('Save changes')      // no editing form
            ->assertDontSee('Delete group')      // no danger zone
            ->assertDontSee('x-data="{ editing', false); // no Alpine edit toggle for managers

        $this->actingAs($manager)
            ->get(route('groups.members.index', $group))
            ->assertOk()
            ->assertSee($parent->name)           // nesting lists render read-only
            ->assertSee($child->name)
            ->assertDontSee(route('groups.parents.store', $group))
            ->assertDontSee(route('groups.children.store', $group))
            ->assertDontSee(route('groups.parents.destroy', ['group' => $group, 'parent' => $parent->id]))
            ->assertDontSee(route('groups.children.destroy', ['group' => $group, 'child' => $child->id]));
    }
}
