<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('oauth_clients', 'provider')) {
            Schema::table('oauth_clients', function (Blueprint $table): void {
                $table->string('provider')->after('secret')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_clients', function ($table): void {
            $table->dropColumn('provider');
        });
    }
};
