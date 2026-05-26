<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupAttributeValue extends Model
{
    public $timestamps = false;
    protected $fillable = ['group_id', 'attribute_definition_id', 'value'];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(GroupAttributeDefinition::class, 'attribute_definition_id');
    }
}
