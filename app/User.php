<?php

declare(strict_types=1);

namespace App;

use App\Models\Group;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use League\OAuth2\Client\Token\AccessToken;

#[Fillable([
    'id', 'email', 'first_name', 'last_name', 'rating', 'rating_short', 'rating_long', 'rating_grp', 'pilot_rating', 'pilot_rating_short', 'pilot_rating_long', 'country', 'region', 'division', 'subdivision', 'atc_active', 'accepted_privacy', 'reg_date', 'last_login', 'access_token', 'refresh_token', 'token_expires',
])]
#[Hidden([
    'remember_token',
])]
#[WithoutIncrementing]
#[WithoutTimestamps]
class User extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens, HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Return ban data if user is banned
     *
     * @return BannedUser
     */
    public function banned()
    {
        return $this->hasOne(BannedUser::class);
    }

    /**
     * @return BelongsToMany<Group, $this, Pivot>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user')
            ->withPivot('added_by', 'created_at');
    }

    /**
     * When doing $user->token, return a valid access token or null if none exists
     *
     * @return AccessToken
     * @return null
     */
    protected function getTokenAttribute()
    {
        if ($this->access_token === null) {
            return null;
        }
        $token = new AccessToken([
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires' => $this->token_expires,
        ]);
        if ($token->hasExpired()) {
            $token = OAuthController::updateToken($token);
        }
        // Can't put it inside the "if token expired"; $this is null there
        // but anyway Laravel will only update if any changes have been made.
        $this->update([
            'access_token' => ($token) ? $token->getToken() : null,
            'refresh_token' => ($token) ? $token->getRefreshToken() : null,
            'token_expires' => ($token) ? $token->getExpires() : null,
        ]);

        return $token;
    }
}
