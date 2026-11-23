<?php

namespace Tests\Feature\OAuth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DropLegacyClientColumnsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const string MIGRATION = '2026_11_19_900000_drop_legacy_oauth_client_columns.php';

    private const array LEGACY_COLUMNS = ['user_id', 'redirect', 'personal_access_client', 'password_client'];

    public function test_drops_the_legacy_columns_and_the_personal_access_clients_table(): void
    {
        foreach (self::LEGACY_COLUMNS as $column) {
            $this->assertFalse(Schema::hasColumn('oauth_clients', $column), "oauth_clients.$column");
        }
        $this->assertFalse(Schema::hasTable('oauth_personal_access_clients'));
    }

    public function test_keeps_existing_clients(): void
    {
        $this->migration()->down();
        DB::table('oauth_clients')->insert([
            'id' => '5',
            'name' => 'Control Center',
            'secret' => 'hashed-secret',
            'redirect_uris' => '["https://cc.test/callback"]',
            'grant_types' => '["authorization_code","refresh_token"]',
            'redirect' => 'https://cc.test/callback',
            'revoked' => false,
        ]);

        $this->migration()->up();

        $client = DB::table('oauth_clients')->find('5');
        $this->assertSame('Control Center', $client->name);
        $this->assertSame('["https://cc.test/callback"]', $client->redirect_uris);
        $this->assertSame('["authorization_code","refresh_token"]', $client->grant_types);
    }

    public function test_down_re_adds_the_columns_and_the_table(): void
    {
        $this->migration()->down();

        foreach (self::LEGACY_COLUMNS as $column) {
            $this->assertTrue(Schema::hasColumn('oauth_clients', $column), "oauth_clients.$column");
        }
        $this->assertTrue(Schema::hasTable('oauth_personal_access_clients'));
    }

    private function migration(): object
    {
        return require database_path('migrations/'.self::MIGRATION);
    }
}
