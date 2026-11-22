<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the pre-Passport 13 client columns and the personal access clients
 * table. See ADR 0002. Nothing reads them since the client schema migration
 * backfilled redirect_uris and grant_types.
 *
 * down() re-adds them empty, in the shape the client schema migration left
 * them, so that migration can roll back. To roll back a deployment, restore
 * the backup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            // SQLite refuses to drop an indexed column.
            $table->dropIndex(['user_id']);
            $table->dropColumn(['user_id', 'redirect', 'personal_access_client', 'password_client']);
        });

        Schema::dropIfExists('oauth_personal_access_clients');
    }

    public function down(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->index()->after('id');
            $table->text('redirect')->nullable()->after('provider');
            $table->boolean('personal_access_client')->default(false)->after('redirect');
            $table->boolean('password_client')->default(false)->after('personal_access_client');
        });

        Schema::create('oauth_personal_access_clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('client_id', 36);
            $table->timestamps();
        });
    }
};
