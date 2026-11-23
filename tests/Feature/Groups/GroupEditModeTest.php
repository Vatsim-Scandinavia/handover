<?php

declare(strict_types=1);

namespace Tests\Feature\Groups;

use App\Models\Group;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An admin who saves a change on the group page stays in edit mode, so a
 * series of edits does not need the Edit button each time.
 */
class GroupEditModeTest extends TestCase
{
    use RefreshDatabase;

    private const string EDITING = 'x-data="{ editing: true';

    private const string READ_ONLY = 'x-data="{ editing: false';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        Group::factory()->admin()->create()->members()->attach($admin->id, ['created_at' => now()]);

        return $admin;
    }

    public function test_group_page_opens_read_only(): void
    {
        $group = Group::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('groups.show', $group))
            ->assertOk()
            ->assertSee(self::READ_ONLY, escape: false);
    }

    public function test_saving_the_group_keeps_edit_mode(): void
    {
        $group = Group::factory()->create(['slug' => 'norway-staff', 'name' => 'Norway staff']);

        $this->actingAs($this->admin())
            ->from(route('groups.show', $group))
            ->followingRedirects()
            ->patch(route('groups.update', $group), ['slug' => 'norway-staff', 'name' => 'Norway FIR staff'])
            ->assertOk()
            ->assertSee('Group updated.')
            ->assertSee(self::EDITING, escape: false);
    }

    public function test_adding_a_parent_keeps_edit_mode(): void
    {
        $group = Group::factory()->create();
        $parent = Group::factory()->create();

        $this->actingAs($this->admin())
            ->from(route('groups.show', $group))
            ->followingRedirects()
            ->post(route('groups.parents.store', $group), ['parent_id' => $parent->id])
            ->assertOk()
            ->assertSee('Parent group added.')
            ->assertSee(self::EDITING, escape: false);
    }

    public function test_removing_a_child_keeps_edit_mode(): void
    {
        $group = Group::factory()->create();
        $child = Group::factory()->create();
        $group->children()->attach($child->id);

        $this->actingAs($this->admin())
            ->from(route('groups.show', $group))
            ->followingRedirects()
            ->delete(route('groups.children.destroy', ['group' => $group, 'child' => $child->id]))
            ->assertOk()
            ->assertSee('Child group removed.')
            ->assertSee(self::EDITING, escape: false);
    }
}
