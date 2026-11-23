<?php

declare(strict_types=1);

namespace App;

use App\Models\Group;
use App\Vatsim\AsVatsimToken;
use App\Vatsim\Member;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use League\OAuth2\Client\Token\AccessToken;

/**
 * @property AccessToken|null $vatsim_token
 */
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

    protected function casts(): array
    {
        return [
            'vatsim_token' => AsVatsimToken::class,
        ];
    }

    /**
     * Copies the member's VATSIM data onto the user. Does not save.
     */
    public function fillFromVatsim(Member $member): static
    {
        return $this->fill([
            'email' => $member->email,
            'first_name' => $member->firstName,
            'last_name' => $member->lastName,
            'rating' => $member->rating,
            'rating_short' => $member->ratingShort,
            'rating_long' => $member->ratingLong,
            'pilot_rating' => $member->pilotRating,
            'pilot_rating_short' => $member->pilotRatingShort,
            'pilot_rating_long' => $member->pilotRatingLong,
            'country' => $member->country,
            'region' => $member->region,
            'division' => $member->division,
            'subdivision' => $member->subdivision,
        ]);
    }

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
     * Users matching every word of $term. A word matches the start of the
     * first or last name; a number also matches the start of the CID.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function matching(Builder $query, string $term): void
    {
        $words = preg_split('/\s+/', trim($term), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words === false ? [] : $words as $word) {
            $prefix = $word.'%';
            $query->where(function (Builder $q) use ($word, $prefix): void {
                $q->where('first_name', 'like', $prefix)->orWhere('last_name', 'like', $prefix);
                if (ctype_digit($word)) {
                    $q->orWhere('users.id', 'like', $prefix);
                }
            });
        }
    }
}
