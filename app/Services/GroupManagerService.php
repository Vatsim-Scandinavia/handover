<?php
namespace App\Services;

use App\Models\Group;
use App\Models\GroupManagerRuleByAttribute;
use App\Models\GroupManagerRuleByGroup;
use App\Models\GroupManagerRuleByTag;
use App\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GroupManagerService
{
    public function isAdmin(User $user): bool
    {
        return $user->groups()->where('is_admin_group', true)->exists();
    }

    public function canManage(User $user, Group $group): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }
        return in_array($group->id, $this->manageableGroupIds($user), strict: true);
    }

    public function manageableGroupIds(User $user): array
    {
        $version = Cache::get('groups:cache_version', 0);
        return Cache::remember(
            "user:{$user->id}:manageable_groups:v{$version}",
            300,
            fn () => $this->resolveManageableGroupIds($user)
        );
    }

    public function incrementCacheVersion(): void
    {
        Cache::increment('groups:cache_version');
    }

    public function grantingRulesFor(User $user, Group $group): array
    {
        $userGroupIds = $user->groups()->pluck('groups.id')->toArray();
        if (empty($userGroupIds)) {
            return [];
        }

        $rules = [];

        GroupManagerRuleByGroup::whereIn('manager_group_id', $userGroupIds)
            ->where('target_group_id', $group->id)
            ->with('managerGroup')
            ->get()
            ->each(fn ($r) => $rules[] = "Member of \"{$r->managerGroup->name}\"");

        $groupTags = $group->tags->pluck('tag')->toArray();
        if (!empty($groupTags)) {
            GroupManagerRuleByTag::whereIn('manager_group_id', $userGroupIds)
                ->whereIn('target_tag', $groupTags)
                ->with('managerGroup')
                ->get()
                ->each(fn ($r) => $rules[] = "Member of \"{$r->managerGroup->name}\" (via tag \"{$r->target_tag}\")");
        }

        $group->loadMissing('attributeValues.definition');
        $groupAttrPairs = $group->attributeValues->mapWithKeys(fn ($av) => [
            $av->definition->key => $av->value
        ])->all();

        GroupManagerRuleByAttribute::whereIn('manager_group_id', $userGroupIds)
            ->with('managerGroup')
            ->get()
            ->each(function ($r) use ($groupAttrPairs, &$rules) {
                if (($groupAttrPairs[$r->target_attribute_key] ?? null) === $r->target_attribute_value) {
                    $rules[] = "Member of \"{$r->managerGroup->name}\" (via {$r->target_attribute_key}={$r->target_attribute_value})";
                }
            });

        return $rules;
    }

    private function resolveManageableGroupIds(User $user): array
    {
        $userGroupIds = $user->groups()->pluck('groups.id')->toArray();
        if (empty($userGroupIds)) {
            return [];
        }

        $byGroup = GroupManagerRuleByGroup::whereIn('manager_group_id', $userGroupIds)
            ->pluck('target_group_id')
            ->toArray();

        $tags = GroupManagerRuleByTag::whereIn('manager_group_id', $userGroupIds)
            ->pluck('target_tag')
            ->toArray();
        $byTag = empty($tags) ? [] : Group::whereHas('tags', fn ($q) => $q->whereIn('tag', $tags))
            ->pluck('id')
            ->toArray();

        $attrRules = GroupManagerRuleByAttribute::whereIn('manager_group_id', $userGroupIds)->get();
        $byAttribute = [];
        if ($attrRules->isNotEmpty()) {
            $byAttribute = DB::table('group_attribute_values')
                ->join('group_attribute_definitions', 'group_attribute_definitions.id', '=', 'group_attribute_values.attribute_definition_id')
                ->where(function ($q) use ($attrRules) {
                    foreach ($attrRules as $rule) {
                        $q->orWhere(fn ($w) => $w
                            ->where('group_attribute_definitions.key', $rule->target_attribute_key)
                            ->where('group_attribute_values.value', $rule->target_attribute_value));
                    }
                })
                ->pluck('group_attribute_values.group_id')
                ->toArray();
        }

        return array_values(array_unique(array_merge($byGroup, $byTag, $byAttribute)));
    }
}
