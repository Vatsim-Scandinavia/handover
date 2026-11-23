<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the allowed origins of public clients to the client policy. Every
 * existing client starts with none. See CONTEXT.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_client_policies', function (Blueprint $table): void {
            // MySQL takes a JSON default only as an expression. SQLite can't
            // add a column with an expression default, but takes a literal.
            $table->json('allowed_origins')
                ->default(Schema::getConnection()->getDriverName() === 'sqlite' ? '[]' : new Expression("('[]')"))
                ->after('allowed_scopes');
        });
    }

    public function down(): void
    {
        Schema::table('oauth_client_policies', function (Blueprint $table): void {
            $table->dropColumn('allowed_origins');
        });
    }
};
