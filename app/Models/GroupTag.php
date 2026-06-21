<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupTag extends Model
{
    public $timestamps = false;
    protected $fillable = ['group_id', 'tag'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
