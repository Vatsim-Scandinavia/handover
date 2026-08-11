<?php
namespace Tests\Feature\Groups;

use App\Models\Group;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        \App\Models\GroupManagerRuleByGroup::create([
            'manager_group_id' => $managerGroup->id,
            'target_group_id'  => $child->id,
        ]);

        $this->actingAs($manager)
            ->post(route('groups.parents.store', $child), ['parent_id' => $parent->id])
            ->assertForbidden();
    }

    public function test_show_page_lists_parent_and_child_groups(): void
    {
        $group = Group::factory()->create(['name' => 'vACC Norway']);
        $parent = Group::factory()->create(['name' => 'EUR Region']);
        $child = Group::factory()->create(['name' => 'Norway Trainees']);
        DB::table('group_hierarchy')->insert(['parent_id' => $parent->id, 'child_id' => $group->id]);
        DB::table('group_hierarchy')->insert(['parent_id' => $group->id, 'child_id' => $child->id]);

        $this->actingAs($this->admin())
            ->get(route('groups.show', $group))
            ->assertOk()
            ->assertSee('EUR Region')
            ->assertSee('Norway Trainees');
    }

    public function test_edit_page_shows_parent_control_and_admin_hint(): void
    {
        $group = Group::factory()->create();
        $adminParent = Group::factory()->admin()->create(['name' => 'System Administrators']);

        $this->actingAs($this->admin())
            ->get(route('groups.edit', $group))
            ->assertOk()
            ->assertSee('Parent groups')
            ->assertSee('does not grant admin'); // hint text near admin-group options
    }
}
