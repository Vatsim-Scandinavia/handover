<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['manager_group_id', 'target_group_id'])]
#[Table(name: 'group_manager_rules_by_group')]
class GroupManagerRuleByGroup extends Model
{
    /**
     * @return BelongsTo<Group, $this>
     */
    public function managerGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'manager_group_id');
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function targetGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'target_group_id');
    }
}
