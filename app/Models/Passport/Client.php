<?php
 
namespace App\Models\Passport;
 
use Illuminate\Contracts\Auth\Authenticatable;
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
}