<?php

declare(strict_types=1);

namespace App\Models\Passport;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Laravel\Passport\Client as BaseClient;

class Client extends BaseClient
{
    protected static function booted(): void
    {
        // Every client has a policy. The admin UI sets its fields right after.
        static::created(function (Client $client): void {
            $client->setRelation('policy', $client->policy()->create(['allowed_scopes' => ClientPolicy::DEFAULT_SCOPES]));
        });

        // A revoked client admits nobody, so drop its required groups. Otherwise
        // they could never be deleted, since revoked clients are not listed.
        static::updated(function (Client $client): void {
            if ($client->wasChanged('revoked') && $client->revoked) {
                $client->policy?->requiredGroups()->detach();
            }
        });
    }

    /**
     * @return HasOne<ClientPolicy, $this>
     */
    public function policy(): HasOne
    {
        return $this->hasOne(ClientPolicy::class, 'client_id');
    }

    /**
     * Determine if the client should skip the authorization prompt.
     *
     * @param  array<int, string>  $scopes
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return true;
    }

    /**
     * Accept the numeric ids of clients made before 4.0.0 as well as UUIDs,
     * so route binding still finds legacy clients.
     */
    protected function isValidUniqueId($value): bool
    {
        return Str::isUuid($value) || ctype_digit((string) $value);
    }
}
