<?php
namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['slug', 'name', 'description', 'is_admin_group'];
    protected $casts = ['is_admin_group' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function tags(): HasMany
    {
        return $this->hasMany(GroupTag::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(GroupAttributeValue::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->withPivot('added_by', 'created_at');
    }

    // Rules FROM this group (what it manages)
    public function managerRulesByGroup(): HasMany
    {
        return $this->hasMany(GroupManagerRuleByGroup::class, 'manager_group_id');
    }

    public function managerRulesByTag(): HasMany
    {
        return $this->hasMany(GroupManagerRuleByTag::class, 'manager_group_id');
    }

    public function managerRulesByAttribute(): HasMany
    {
        return $this->hasMany(GroupManagerRuleByAttribute::class, 'manager_group_id');
    }

    // Rules TO this group (who can manage it) — used on the rules management page
    public function targetedByGroupRules(): HasMany
    {
        return $this->hasMany(GroupManagerRuleByGroup::class, 'target_group_id');
    }
}
