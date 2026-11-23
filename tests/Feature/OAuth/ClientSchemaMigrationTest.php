<?php

declare(strict_types=1);

namespace Tests\Feature\OAuth;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Client as PassportClient;
use Laravel\Passport\Passport;
use Tests\Concerns\RunsOAuthFlows;
use Tests\TestCase;

class ClientSchemaMigrationTest extends TestCase
{
    use RefreshDatabase;
    use RunsOAuthFlows;

    private const string MIGRATION = '2026_11_19_000002_adopt_passport_13_client_schema.php';

    /** Depends on the new schema, so it rolls back first and migrates last. */
    private const string POLICY_MIGRATION = '2026_11_19_000003_create_oauth_client_policies.php';

    protected function setUp(): void
    {
        parent::setUp();

        // Run the tests against the 3.x schema, then migrate forward.
        $this->migration('2026_11_19_900000_drop_legacy_oauth_client_columns.php')->down();
        $this->migration(self::POLICY_MIGRATION)->down();
        $this->migration()->down();
    }

    public function test_backfills_grant_types_exactly_as_passport_derived_them(): void
    {
        $combinations = Arr::crossJoin(
            [null, 'hashed-secret'],
            [null, 7],
            ['', 'https://a.test/cb', 'https://a.test/cb,https://b.test/cb'],
            [false, true],
            [false, true],
        );

        $expected = [];
        foreach ($combinations as $i => [$secret, $userId, $redirect, $password, $personal]) {
            $row = $this->legacyClient($i + 1, [
                'secret' => $secret,
                'user_id' => $userId,
                'redirect' => $redirect,
                'password_client' => $password,
                'personal_access_client' => $personal,
            ]);
            $derived = (new PassportClient)->setRawAttributes($row);
            $expected[$i + 1] = [$derived->grant_types, $derived->redirect_uris];
        }

        $this->migrateForward();

        foreach ($expected as $id => [$grantTypes, $redirectUris]) {
            $row = DB::table('oauth_clients')->find((string) $id);
            $this->assertSame($grantTypes, json_decode($row->grant_types, true), "grant_types of client $id");
            $this->assertSame($redirectUris, json_decode($row->redirect_uris, true), "redirect_uris of client $id");
            $this->assertNull($row->owner_type);
            $this->assertNull($row->owner_id);
        }
    }

    public function test_keeps_integer_ids_as_strings_in_every_client_id_column(): void
    {
        $this->legacyClient(5);
        DB::table('oauth_access_tokens')->insert([
            'id' => 'token-1', 'user_id' => 1, 'client_id' => 5, 'revoked' => false,
            'created_at' => now(), 'updated_at' => now(), 'expires_at' => now()->addHour(),
        ]);
        DB::table('oauth_auth_codes')->insert([
            'id' => 'code-1', 'user_id' => 1, 'client_id' => 5, 'revoked' => false, 'expires_at' => now(),
        ]);

        $this->migrateForward();

        $this->assertSame('5', DB::table('oauth_clients')->value('id'));
        $this->assertSame('5', DB::table('oauth_access_tokens')->value('client_id'));
        $this->assertSame('5', DB::table('oauth_auth_codes')->value('client_id'));

        foreach (['oauth_clients' => 'id', 'oauth_access_tokens' => 'client_id', 'oauth_auth_codes' => 'client_id', 'oauth_personal_access_clients' => 'client_id'] as $table => $column) {
            $this->assertSame('varchar', Schema::getColumnType($table, $column), "$table.$column");
        }
    }

    public function test_a_legacy_client_completes_auth_code_and_refresh(): void
    {
        $this->legacyClient(5, ['secret' => Hash::make('legacy-secret'), 'redirect' => $this->redirectUri]);

        $this->migrateForward();

        $client = Passport::client()->newQuery()->find('5');
        $client->plainSecret = 'legacy-secret';

        $tokens = $this->signIn(User::factory()->create(), $client);
        $this->refresh($client, $tokens['refresh_token'])->assertOk();
    }

    public function test_a_new_client_after_the_migration_gets_a_uuid(): void
    {
        $this->legacyClient(5);

        $this->migrateForward();

        $client = $this->makeClient();
        $this->assertTrue(str($client->getKey())->isUuid());
        $this->assertSame(2, DB::table('oauth_clients')->count());
    }

    private function migration(string $file = self::MIGRATION): object
    {
        return require database_path('migrations/'.$file);
    }

    private function migrateForward(): void
    {
        $this->migration()->up();
        $this->migration(self::POLICY_MIGRATION)->up();
    }

    /**
     * Insert a client the way 3.x stored it and return the raw row.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function legacyClient(int $id, array $overrides = []): array
    {
        $row = array_merge([
            'id' => $id,
            'user_id' => null,
            'name' => "Client $id",
            'secret' => 'hashed-secret',
            'provider' => null,
            'redirect' => 'https://client.test/callback',
            'personal_access_client' => false,
            'password_client' => false,
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        DB::table('oauth_clients')->insert($row);

        return $row;
    }
}
