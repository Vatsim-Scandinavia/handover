<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupManagerRuleByAttribute extends Model
{
    protected $table = 'group_manager_rules_by_attribute';
    protected $fillable = ['manager_group_id', 'target_attribute_key', 'target_attribute_value'];

    public function managerGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'manager_group_id');
    }
}
