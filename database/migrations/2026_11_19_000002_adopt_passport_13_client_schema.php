<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves oauth_clients to Passport 13's layout. See ADR 0002.
 *
 * Client ids become strings, so new clients can get UUIDs while existing
 * clients keep their numbers, such as "5". redirect_uris and grant_types are
 * backfilled with exactly what Passport derived from the legacy columns, so
 * every client keeps the grants it had. The legacy columns stay for now, but
 * new clients no longer fill them.
 */
return new class extends Migration
{
    private const array CLIENT_ID_TABLES = ['oauth_auth_codes', 'oauth_access_tokens', 'oauth_personal_access_clients'];

    public function up(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->string('id', 36)->change();
        });

        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->nullableMorphs('owner');
            $table->text('redirect_uris')->nullable();
            $table->text('grant_types')->nullable();
            $table->text('redirect')->nullable()->change();
            $table->boolean('personal_access_client')->default(false)->change();
            $table->boolean('password_client')->default(false)->change();
        });

        foreach (self::CLIENT_ID_TABLES as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->string('client_id', 36)->change();
            });
        }

        foreach (DB::table('oauth_clients')->get() as $client) {
            $redirectUris = empty($client->redirect) ? [] : explode(',', $client->redirect);

            DB::table('oauth_clients')->where('id', $client->id)->update([
                'redirect_uris' => json_encode($redirectUris),
                'grant_types' => json_encode($this->legacyGrantTypes($client, $redirectUris)),
            ]);
        }

        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->text('redirect_uris')->nullable(false)->change();
            $table->text('grant_types')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('oauth_clients')->pluck('id')->contains(fn ($id): bool => ! ctype_digit((string) $id))) {
            throw new RuntimeException('Clients with UUID ids exist. Restore the 3.x backup instead of rolling back.');
        }

        foreach (DB::table('oauth_clients')->get() as $client) {
            $grantTypes = json_decode($client->grant_types, true);

            DB::table('oauth_clients')->where('id', $client->id)->update([
                'redirect' => implode(',', json_decode($client->redirect_uris, true)),
                'personal_access_client' => in_array('personal_access', $grantTypes),
                'password_client' => in_array('password', $grantTypes),
            ]);
        }

        foreach (self::CLIENT_ID_TABLES as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->unsignedBigInteger('client_id')->change();
            });
        }

        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->dropMorphs('owner');
            $table->dropColumn(['redirect_uris', 'grant_types']);
            $table->text('redirect')->nullable(false)->change();
            $table->boolean('personal_access_client')->default(null)->change();
            $table->boolean('password_client')->default(null)->change();
        });

        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->bigIncrements('id')->change();
        });
    }

    /**
     * Passport\Client::grantTypes() for a client with no grant_types column.
     *
     * @param  string[]  $redirectUris
     * @return string[]
     */
    private function legacyGrantTypes(object $client, array $redirectUris): array
    {
        $confidential = ! empty($client->secret);

        return array_keys(array_filter([
            'authorization_code' => $redirectUris !== [],
            'client_credentials' => $confidential && empty($client->user_id),
            'implicit' => $redirectUris !== [],
            'password' => (bool) $client->password_client,
            'personal_access' => (bool) $client->personal_access_client && $confidential,
            'refresh_token' => true,
            'urn:ietf:params:oauth:grant-type:device_code' => true,
        ]));
    }
};
