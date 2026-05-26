<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupManagerRuleByGroup extends Model
{
    protected $table = 'group_manager_rules_by_group';
    protected $fillable = ['manager_group_id', 'target_group_id'];

    public function managerGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'manager_group_id');
    }

    public function targetGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'target_group_id');
    }
}
