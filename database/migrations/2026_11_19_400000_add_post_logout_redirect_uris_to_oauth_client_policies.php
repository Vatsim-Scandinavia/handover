<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the post-logout redirect URIs to the client policy. Every existing
 * client starts with none. See CONTEXT.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_client_policies', function (Blueprint $table) {
            // MySQL takes a JSON default only as an expression. SQLite can't
            // add a column with an expression default, but takes a literal.
            $table->json('post_logout_redirect_uris')
                ->default(Schema::getConnection()->getDriverName() === 'sqlite' ? '[]' : new Expression("('[]')"))
                ->after('allowed_origins');
        });
    }

    public function down(): void
    {
        Schema::table('oauth_client_policies', function (Blueprint $table) {
            $table->dropColumn('post_logout_redirect_uris');
        });
    }
};
