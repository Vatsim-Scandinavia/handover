<?php
namespace Tests\Feature\Groups;

use App\Models\Group;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TransitiveMembershipTest extends TestCase
{
    use RefreshDatabase;

    private function nest(Group $child, Group $parent): void
    {
        DB::table('group_hierarchy')->insert(['parent_id' => $parent->id, 'child_id' => $child->id]);
    }

    /** @return array<string,array<string,mixed>> keyed by slug */
    private function groupsBySlug(array $groups): array
    {
        return collect($groups)->keyBy('slug')->toArray();
    }

    public function test_api_includes_inherited_groups_flagged_not_direct(): void
    {
        $user = User::factory()->create();
        $child = Group::factory()->create(['slug' => 'vacc-norway', 'name' => 'vACC Norway']);
        $parent = Group::factory()->create(['slug' => 'eur-region', 'name' => 'EUR Region']);
        $this->nest($child, $parent);
        $child->members()->attach($user->id, ['created_at' => now()]);
        Passport::actingAs($user, ['groups']);

        $groups = $this->groupsBySlug(
            $this->getJson('/api/user')->assertStatus(200)->json('data.groups')
        );

        $this->assertTrue($groups['vacc-norway']['direct']);
        $this->assertFalse($groups['eur-region']['direct']);
    }

    public function test_api_marks_group_direct_when_reachable_both_ways(): void
    {
        $user = User::factory()->create();
        $child = Group::factory()->create(['slug' => 'child']);
        $parent = Group::factory()->create(['slug' => 'parent']);
        $this->nest($child, $parent);
        $child->members()->attach($user->id, ['created_at' => now()]);
        $parent->members()->attach($user->id, ['created_at' => now()]); // also direct
        Passport::actingAs($user, ['groups']);

        $groups = $this->groupsBySlug(
            $this->getJson('/api/user')->assertStatus(200)->json('data.groups')
        );

        $this->assertCount(2, $groups);               // appears once
        $this->assertTrue($groups['parent']['direct']); // direct wins
    }

    public function test_dashboard_lists_direct_and_inherited_groups(): void
    {
        $user = User::factory()->create();
        $child = Group::factory()->create(['name' => 'vACC Norway']);
        $parent = Group::factory()->create(['name' => 'EUR Region']);
        $this->nest($child, $parent);
        $child->members()->attach($user->id, ['created_at' => now()]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('vACC Norway')
            ->assertSee('EUR Region')
            ->assertSee('Inherited via vACC Norway');
    }
}
