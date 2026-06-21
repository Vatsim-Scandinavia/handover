<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupAttributeDefinition extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'label'];

    public function values(): HasMany
    {
        return $this->hasMany(GroupAttributeValue::class, 'attribute_definition_id');
    }
}
