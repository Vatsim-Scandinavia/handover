<?php
 
namespace App\Models\Passport;
 
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use Laravel\Passport\Client as BaseClient;
 
class Client extends BaseClient
{
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
