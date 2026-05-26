<?php

namespace App;

use App\Http\Controllers\VatsimOAuthController;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use League\OAuth2\Client\Token\AccessToken;
use Laravel\Passport\HasApiTokens;


class User extends Authenticatable
{

    use HasApiTokens, HasFactory;

    public $timestamps = false;
    public $incrementing = false;

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
        'id', 'email', 'first_name', 'last_name', 'rating', 'rating_short', 'rating_long', 'rating_grp', 'pilot_rating', 'pilot_rating_short', 'pilot_rating_long', 'country', 'region', 'division', 'subdivision', 'atc_active', 'accepted_privacy', 'reg_date', 'last_login', 'access_token', 'refresh_token', 'token_expires'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token'
    ];

    /**
     * Return ban data if user is banned
     *
     * @return \App\BannedUser
     */
    public function banned(){
        return $this->hasOne(BannedUser::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user')
            ->withPivot('added_by', 'created_at');
    }

     /**
     * When doing $user->token, return a valid access token or null if none exists
     * 
     * @return \League\OAuth2\Client\Token\AccessToken 
     * @return null
     */
    public function getTokenAttribute()
    {
        if ($this->access_token === null) return null;
        else {
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

}