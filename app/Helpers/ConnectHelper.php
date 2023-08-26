<?php

namespace App\Helpers;

use App\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ConnectHelper
{

    protected $client;
    protected $baseUrl;

    public function __construct()
    {
        $this->client = $this->client();
        $this->baseUrl = config('vatsim_auth.base');
    }

    protected function client() {
        return new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Content-type' => 'application/json',
            ],
        ]);
    }

    public function fetchUser(User $user) {

        try {
            $response = $this->client->get($this->baseUrl . '/api/user', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $user->access_token,
                ],
            ]);
            
            return json_decode($response->getBody());
        } catch(\League\OAuth2\Server\Exception\OAuthServerException $exception){
            return false;
        } catch(\Exception $exception) {
            Log::critical($exception->getMessage());;
        }

    }

    public function refreshToken(User $user) {
        try {
            $response = $this->client->post($this->baseUrl . '/oauth/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $user->refresh_token,
                    'client_id' => config('vatsim_auth.id'),
                    'client_secret' => config('vatsim_auth.secret'),
                    'scope' => str_replace(',', ' ', config('vatsim_auth.scopes')),
                ]
            ]);

            if ($response->getStatusCode() != 200) {
                return false;
            }

            return json_decode($response->getBody());
        } catch(\League\OAuth2\Server\Exception\OAuthServerException $exception){
            return false;
        } catch(\Exception $exception) {
            Log::critical($exception->getMessage());;
        }

    }
}