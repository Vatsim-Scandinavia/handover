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
        $this->baseUrl = env('VATSIM_OAUTH_BASE', 'https://auth.vatsim.net');
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
                    'client_id' => env('VATSIM_OAUTH_CLIENT'),
                    'client_secret' => env('VATSIM_OAUTH_SECRET'),
                    'scope' => str_replace(',', ' ', env('VATSIM_OAUTH_SCOPES')),
                ]
            ]);

            if ($response->getStatusCode() != 200) {
                return false;
            }

            return json_decode($response->getBody());
        } catch(\Exception $exception) {
            Log::critical($exception->getMessage());
        }

    }
}