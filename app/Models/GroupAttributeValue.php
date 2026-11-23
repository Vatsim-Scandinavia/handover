<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['group_id', 'attribute_definition_id', 'value'])]
#[WithoutTimestamps]
class GroupAttributeValue extends Model
{
    /**
     * @return BelongsTo<GroupAttributeDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(GroupAttributeDefinition::class, 'attribute_definition_id');
    }
}
