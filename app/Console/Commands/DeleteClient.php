<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Passport\Client;

class DeleteClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:client:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a client by id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $clientId = $this->ask('Which client id do you want to revoke?');
        $client = Client::find($clientId);

        if ($this->confirm('Are you sure you want to delete the client \'' . $client->name . '\'?')) {
            $clientAccessTokens = $client->tokens;

            $refreshTokenRepository = app('Laravel\Passport\RefreshTokenRepository');

            foreach($clientAccessTokens as $clientAccessToken) {
                // Revoke the refresh token
                $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($clientAccessToken->id);

                // Revoke the access token
                $clientAccessToken->revoke();
            }

            // Revoke the client
            $client->update(['revoked' => true]);
            $this->info('Client revoked successfully.');
        } else {
            $this->warn('Client revoke aborted.');
        }

        

    }
}
