<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupManagerRuleByTag extends Model
{
    protected $table = 'group_manager_rules_by_tag';
    protected $fillable = ['manager_group_id', 'target_tag'];

    public function managerGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'manager_group_id');
    }
}
