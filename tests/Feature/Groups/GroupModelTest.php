<?php
namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Models\GroupTag;
use App\Models\GroupAttributeDefinition;
use App\Models\GroupAttributeValue;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_has_uuid_primary_key(): void
    {
        $group = Group::factory()->create();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $group->id
        );
    }

    public function test_group_has_tags_relationship(): void
    {
        $group = Group::factory()->create();
        GroupTag::create(['group_id' => $group->id, 'tag' => 'vacc']);
        $this->assertCount(1, $group->fresh()->tags);
        $this->assertEquals('vacc', $group->fresh()->tags->first()->tag);
    }

    public function test_group_has_attribute_values_relationship(): void
    {
        $def = GroupAttributeDefinition::factory()->create(['key' => 'region']);
        $group = Group::factory()->create();
        GroupAttributeValue::create([
            'group_id' => $group->id,
            'attribute_definition_id' => $def->id,
            'value' => 'EUR',
        ]);
        $this->assertCount(1, $group->fresh()->attributeValues);
    }

    public function test_group_has_members_relationship(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create();
        $group->members()->attach($user->id, ['created_at' => now()]);
        $this->assertCount(1, $group->fresh()->members);
    }

    public function test_user_has_groups_relationship(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create();
        $group->members()->attach($user->id, ['created_at' => now()]);
        $this->assertCount(1, $user->fresh()->groups);
    }

    public function test_group_hierarchy_relations_resolve_parents_and_children(): void
    {
        $parent = \App\Models\Group::factory()->create();
        $child = \App\Models\Group::factory()->create();

        \Illuminate\Support\Facades\DB::table('group_hierarchy')->insert([
            'parent_id' => $parent->id,
            'child_id'  => $child->id,
        ]);

        $this->assertTrue($child->parents->contains($parent));
        $this->assertTrue($parent->children->contains($child));
        $this->assertFalse($parent->parents->contains($child));
    }
}
