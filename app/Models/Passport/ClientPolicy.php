<?php

namespace App\Models\Passport;

use App\Models\Group;
use App\OAuth\Origin;
use App\Services\GroupMembershipResolver;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

/**
 * The rules Handover enforces for one client beyond OAuth itself.
 * Every client has exactly one. See CONTEXT.md and ADR 0002.
 *
 * @property string $client_id
 * @property list<string> $allowed_scopes
 * @property list<string> $allowed_origins The origins a public client may call the token and userinfo endpoints from, as Origin::of() forms them.
 * @property bool $single_session
 */
class ClientPolicy extends Model
{
    /**
     * The scopes a client gets when it requests none, limited to its allowed
     * scopes. Clients created outside the admin UI may request these.
     */
    public const DEFAULT_SCOPES = ['full_name', 'email', 'vatsim_details', 'country'];

    protected $table = 'oauth_client_policies';

    protected $primaryKey = 'client_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['client_id', 'allowed_scopes', 'allowed_origins', 'single_session'];

    protected $attributes = ['single_session' => true];

    protected function casts(): array
    {
        return [
            'allowed_scopes' => 'array',
            'allowed_origins' => 'array',
            'single_session' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /** The groups whose members may sign in. None means every member may. */
    public function requiredGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'oauth_client_required_groups', 'client_id', 'group_id');
    }

    /** Whether a public client may call the token and userinfo endpoints from the origin. */
    public function allowsOrigin(string $origin): bool
    {
        return in_array($origin, $this->allowed_origins, true);
    }

    /** Whether some active client allows the origin. */
    public static function anyAllowsOrigin(string $origin): bool
    {
        return static::query()
            ->whereJsonContains('allowed_origins', $origin)
            ->whereHas('client', fn ($client) => $client->where('revoked', false))
            ->exists();
    }

    /**
     * Save the fields the admin UI sent. Fields left out keep their value.
     *
     * @param  array{allowed_scopes?: list<string>, allowed_origins?: list<string>, single_session?: bool, required_groups?: list<string>}  $fields
     */
    public function updateFromAdmin(array $fields): void
    {
        $this->fill(Arr::only($fields, ['allowed_scopes', 'single_session']));

        if (array_key_exists('allowed_origins', $fields)) {
            $this->allowed_origins = array_values(array_unique(array_map(Origin::of(...), $fields['allowed_origins'])));
        }

        $this->save();

        if (array_key_exists('required_groups', $fields)) {
            $this->requiredGroups()->sync($fields['required_groups']);
        }

        // Pick up the column defaults and the synced groups.
        $this->refresh();
    }

    /**
     * The fields the admin UI shows.
     *
     * @return array{allowed_scopes: list<string>, allowed_origins: list<string>, single_session: bool, required_groups: list<string>}
     */
    public function toAdminArray(): array
    {
        return [
            'allowed_scopes' => $this->allowed_scopes,
            'allowed_origins' => $this->allowed_origins,
            'single_session' => $this->single_session,
            'required_groups' => $this->requiredGroups->pluck('id')->sort()->values()->all(),
        ];
    }

    /**
     * Whether the user may sign in to the client: the client requires no
     * group, or the user's effective membership includes one it requires.
     */
    public function admits(User $user): bool
    {
        $required = $this->requiredGroups()->pluck('groups.id')->all();

        return $required === []
            || array_intersect($required, app(GroupMembershipResolver::class)->effectiveGroupIds($user)) !== [];
    }
}
