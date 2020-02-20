<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class BannedUser extends Authenticatable
{

    public $timestamps = false;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'banned_user';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'reason', 'internal_note', 'banned_at',
    ];

}