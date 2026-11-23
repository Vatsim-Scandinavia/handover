<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'label'])]
class GroupAttributeDefinition extends Model
{
    use HasFactory;

    /**
     * @return HasMany<GroupAttributeValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(GroupAttributeValue::class, 'attribute_definition_id');
    }
}
