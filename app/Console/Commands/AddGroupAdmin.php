<?php
namespace App\Console\Commands;

use App\Models\Group;
use App\User;
use Illuminate\Console\Command;

class AddGroupAdmin extends Command
{
    protected $signature = 'groups:add-admin {cid : VATSIM CID to add as system admin}';
    protected $description = 'Add a user to the System Administrators group, creating it if necessary';

    public function handle(): int
    {
        $cid = (int) $this->argument('cid');
        $user = User::find($cid);

        if (!$user) {
            $this->error("No user found with CID {$cid}.");
            return self::FAILURE;
        }

        $existing = Group::where('slug', 'system-administrators')->first();

        if ($existing && !$existing->is_admin_group) {
            $this->error("A group with slug 'system-administrators' already exists but is not an admin group. Resolve this manually.");
            return self::FAILURE;
        }

        $group = $existing ?? Group::create([
            'slug' => 'system-administrators',
            'name' => 'System Administrators',
            'is_admin_group' => true,
        ]);

        if ($group->members()->where('user_id', $user->id)->exists()) {
            $this->info("{$user->first_name} {$user->last_name} (CID: {$cid}) is already a member of '{$group->name}' (UUID: {$group->id}, slug: {$group->slug}).");
            return self::SUCCESS;
        }

        $group->members()->attach($user->id, ['created_at' => now()]);

        $this->info("Added {$user->first_name} {$user->last_name} (CID: {$cid}) to '{$group->name}' (UUID: {$group->id}, slug: {$group->slug}).");
        return self::SUCCESS;
    }
}
