<?php
namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Models\GroupAttributeDefinition;
use App\Models\GroupManagerRuleByGroup;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GroupCrudTest extends TestCase
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

    public function test_admin_can_list_groups(): void
    {
        Group::factory()->create(['name' => 'vACC Norway']);
        $this->actingAs($this->adminUser())
            ->get(route('groups.index'))
            ->assertStatus(200)
            ->assertSee('vACC Norway');
    }

    public function test_manager_sees_only_manageable_groups_on_index(): void
    {
        $user = User::factory()->create();
        $mg = Group::factory()->create(['name' => 'Manager Group']);
        $tg = Group::factory()->create(['name' => 'Target Group']);
        $other = Group::factory()->create(['name' => 'Other Group']);
        $mg->members()->attach($user->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create(['manager_group_id' => $mg->id, 'target_group_id' => $tg->id]);

        $this->actingAs($user)
            ->get(route('groups.index'))
            ->assertStatus(200)
            ->assertSee('Target Group')
            ->assertDontSee('Other Group')
            ->assertDontSee('Manager Group');
    }

    public function test_admin_can_create_group(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('groups.store'), [
                'slug' => 'vacc-norway',
                'name' => 'vACC Norway',
                'description' => 'Norwegian vACC',
                'tags' => ['vacc', 'eur'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('groups', ['slug' => 'vacc-norway']);
        $this->assertDatabaseHas('group_tags', ['tag' => 'vacc']);
        $this->assertDatabaseHas('group_tags', ['tag' => 'eur']);
    }

    public function test_reserved_slug_is_rejected(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('groups.store'), ['slug' => 'admin', 'name' => 'Test'])
            ->assertSessionHasErrors('slug');
    }

    public function test_admin_can_edit_group(): void
    {
        $group = Group::factory()->create(['slug' => 'original']);
        $this->actingAs($this->adminUser())
            ->patch(route('groups.update', $group), [
                'slug' => 'updated',
                'name' => 'Updated Name',
            ])
            ->assertRedirect(route('groups.show', 'updated'));

        $this->assertDatabaseHas('groups', ['slug' => 'updated', 'name' => 'Updated Name']);
    }

    public function test_is_admin_group_toggle_is_logged(): void
    {
        Log::shouldReceive('warning')->once()->with('is_admin_group toggled', \Mockery::any());
        $group = Group::factory()->create(['is_admin_group' => false]);
        $this->actingAs($this->adminUser())
            ->patch(route('groups.update', $group), [
                'slug' => $group->slug,
                'name' => $group->name,
                'is_admin_group' => true,
            ]);
    }

    public function test_cannot_delete_group_with_members(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create();
        $group->members()->attach($user->id, ['created_at' => now()]);

        $this->actingAs($this->adminUser())
            ->delete(route('groups.destroy', $group))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('groups', ['id' => $group->id]);
    }

    public function test_admin_can_delete_empty_group(): void
    {
        $group = Group::factory()->create(['slug' => 'to-delete']);
        $this->actingAs($this->adminUser())
            ->delete(route('groups.destroy', $group))
            ->assertRedirect(route('groups.index'));

        $this->assertDatabaseMissing('groups', ['slug' => 'to-delete']);
    }

    public function test_manager_can_view_group_show(): void
    {
        $manager = User::factory()->create();
        $mg = Group::factory()->create();
        $tg = Group::factory()->create(['name' => 'Target Group']);
        $mg->members()->attach($manager->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create(['manager_group_id' => $mg->id, 'target_group_id' => $tg->id]);

        $this->actingAs($manager)
            ->get(route('groups.show', $tg))
            ->assertStatus(200)
            ->assertSee('Target Group');
    }

    public function test_manager_cannot_view_unmanaged_group(): void
    {
        $manager = User::factory()->create();
        $mg = Group::factory()->create();
        $managed = Group::factory()->create();
        $unmanaged = Group::factory()->create();
        $mg->members()->attach($manager->id, ['created_at' => now()]);
        GroupManagerRuleByGroup::create(['manager_group_id' => $mg->id, 'target_group_id' => $managed->id]);

        $this->actingAs($manager)
            ->get(route('groups.show', $unmanaged))
            ->assertStatus(403);
    }

    public function test_attribute_values_are_saved_on_create(): void
    {
        $def = GroupAttributeDefinition::factory()->create(['key' => 'region']);
        $this->actingAs($this->adminUser())
            ->post(route('groups.store'), [
                'slug' => 'test-group',
                'name' => 'Test',
                'attributes' => [$def->id => 'EUR'],
            ]);

        $group = Group::where('slug', 'test-group')->first();
        $this->assertNotNull($group);
        $this->assertDatabaseHas('group_attribute_values', [
            'group_id' => $group->id,
            'attribute_definition_id' => $def->id,
            'value' => 'EUR',
        ]);
    }
}
