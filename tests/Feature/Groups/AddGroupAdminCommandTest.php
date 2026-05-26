<?php
namespace Tests\Feature\Groups;

use App\Models\Group;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddGroupAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_admin_group_and_adds_user(): void
    {
        $user = User::factory()->create(['id' => 1234567]);

        $this->artisan('groups:add-admin', ['cid' => 1234567])
            ->assertExitCode(0);

        $group = Group::where('slug', 'system-administrators')->first();
        $this->assertNotNull($group);
        $this->assertTrue($group->is_admin_group);
        $this->assertTrue($group->members()->where('user_id', 1234567)->exists());
    }

    public function test_reuses_existing_admin_group(): void
    {
        $user = User::factory()->create(['id' => 1234567]);
        Group::factory()->admin()->create(['slug' => 'system-administrators']);

        $this->artisan('groups:add-admin', ['cid' => 1234567])
            ->assertExitCode(0);

        $this->assertEquals(1, Group::where('slug', 'system-administrators')->count());
    }

    public function test_is_idempotent_when_user_already_member(): void
    {
        $user = User::factory()->create(['id' => 1234567]);
        $group = Group::factory()->admin()->create(['slug' => 'system-administrators']);
        $group->members()->attach(1234567, ['created_at' => now()]);

        $this->artisan('groups:add-admin', ['cid' => 1234567])
            ->assertExitCode(0);

        $this->assertEquals(1, $group->members()->where('user_id', 1234567)->count());
    }

    public function test_fails_when_user_not_found(): void
    {
        $this->artisan('groups:add-admin', ['cid' => 9999999])
            ->assertExitCode(1);
    }

    public function test_fails_when_slug_exists_but_is_not_admin_group(): void
    {
        User::factory()->create(['id' => 1234567]);
        Group::factory()->create(['slug' => 'system-administrators', 'is_admin_group' => false]);

        $this->artisan('groups:add-admin', ['cid' => 1234567])
            ->assertExitCode(1);
    }
}
