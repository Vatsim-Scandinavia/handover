<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the OpenID Connect nonce and auth_time with each auth code, so the
 * id_token issued for the code can carry them. See App\OAuth\Oidc\AuthCodeRepository.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_auth_codes', function (Blueprint $table): void {
            $table->text('nonce')->nullable();
            $table->unsignedBigInteger('auth_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('oauth_auth_codes', function (Blueprint $table): void {
            $table->dropColumn(['nonce', 'auth_time']);
        });
    }
};
