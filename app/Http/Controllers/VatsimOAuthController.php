<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use League\OAuth2\Client\Provider\GenericProvider;

class VatsimOAuthController extends GenericProvider
{
    /**
     * Initializes the provider variable.
     */
    public function __construct()
    {
        parent::__construct([
            'clientId' => config('vatsim_auth.id'),    // The client ID assigned to you by the provider
            'clientSecret' => config('vatsim_auth.secret'),   // The client password assigned to you by the provider
            'redirectUri' => route('login'),
            'urlAuthorize' => config('vatsim_auth.base').'/oauth/authorize',
            'urlAccessToken' => config('vatsim_auth.base').'/oauth/token',
            'urlResourceOwnerDetails' => config('vatsim_auth.base').'/api/user',
            'scopes' => config('vatsim_auth.scopes'),
            'scopeSeparator' => ' ',
        ]);
    }
}
