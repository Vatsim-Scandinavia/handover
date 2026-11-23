<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['manager_group_id', 'target_attribute_key', 'target_attribute_value'])]
#[Table(name: 'group_manager_rules_by_attribute')]
class GroupManagerRuleByAttribute extends Model
{
    /**
     * @return BelongsTo<Group, $this>
     */
    public function managerGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'manager_group_id');
    }
}
