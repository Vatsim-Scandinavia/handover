<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Passport\Client;
use App\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['slug', 'name', 'description', 'is_admin_group'])]
#[RouteKey('slug')]
class Group extends Model
{
    use HasFactory, HasUuids;

    /**
     * @return HasMany<GroupTag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(GroupTag::class);
    }

    /**
     * @return HasMany<GroupAttributeValue, $this>
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(GroupAttributeValue::class);
    }

    /** The group's value for an attribute key, from loaded attributeValues.definition. */
    public function attributeValue(string $key): ?string
    {
        return $this->attributeValues->first(fn (GroupAttributeValue $av): bool => $av->definition->key === $key)?->value;
    }

    /**
     * @return BelongsToMany<User, $this, Pivot>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->withPivot('added_by', 'created_at');
    }

    /**
     * The OAuth clients whose policy requires this group.
     *
     * @return Builder<Client>
     */
    public function requiringClients(): Builder
    {
        return Client::query()->whereHas('policy.requiredGroups', fn (Builder $query) => $query->whereKey($this->id));
    }

    // Groups this group nests INTO (its parents). Roll-up flows toward these.
    /**
     * @return BelongsToMany<Group, $this, Pivot>
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_hierarchy', 'child_id', 'parent_id');
    }

    // Groups nested INTO this group (its children).
    /**
     * @return BelongsToMany<Group, $this, Pivot>
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_hierarchy', 'parent_id', 'child_id');
    }

    /**
     * Every other group as options for the nesting picker. Groups in
     * $linked are already linked, so they show but cannot be picked.
     * Cycles are rejected on submit, not here.
     *
     * @param  Collection<int, Group>  $linked
     * @return array<int, array{id: string, name: string, admin: bool, disabled: bool}>
     */
    public function nestingOptions(Collection $linked): array
    {
        return self::query()->whereKeyNot($this->id)->orderBy('name')->get(['id', 'name', 'is_admin_group'])
            ->map(fn (Group $g): array => [
                'id' => $g->id,
                'name' => $g->name,
                'admin' => $g->is_admin_group,
                'disabled' => $linked->contains('id', $g->id),
            ])->all();
    }

    // Rules FROM this group (what it manages)
    /**
     * @return HasMany<GroupManagerRuleByGroup, $this>
     */
    public function managerRulesByGroup(): HasMany
    {
        return $this->hasMany(GroupManagerRuleByGroup::class, 'manager_group_id');
    }

    /**
     * @return HasMany<GroupManagerRuleByTag, $this>
     */
    public function managerRulesByTag(): HasMany
    {
        return $this->hasMany(GroupManagerRuleByTag::class, 'manager_group_id');
    }

    /**
     * @return HasMany<GroupManagerRuleByAttribute, $this>
     */
    public function managerRulesByAttribute(): HasMany
    {
        return $this->hasMany(GroupManagerRuleByAttribute::class, 'manager_group_id');
    }

    // Rules TO this group (who can manage it) — used on the rules management page
    /**
     * @return HasMany<GroupManagerRuleByGroup, $this>
     */
    public function targetedByGroupRules(): HasMany
    {
        return $this->hasMany(GroupManagerRuleByGroup::class, 'target_group_id');
    }

    protected function casts(): array
    {
        return ['is_admin_group' => 'boolean'];
    }
}
