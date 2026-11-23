<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Passport\Client;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Delete a client by id')]
#[Signature('passport:client:delete')]
class DeleteClient extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $clientId = $this->ask('Which client id do you want to revoke?');
        $client = Client::find($clientId);

        if ($this->confirm('Are you sure you want to delete the client \''.$client->name.'\'?')) {
            $clientAccessTokens = $client->tokens;

            foreach ($clientAccessTokens as $clientAccessToken) {
                // Revoke the refresh token
                $clientAccessToken->refreshToken?->revoke();

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
