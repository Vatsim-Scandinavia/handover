<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable([
    'reason', 'internal_note', 'banned_at',
])]
#[Table(name: 'banned_user', key: 'id')]
#[WithoutTimestamps]
class BannedUser extends Authenticatable {}
