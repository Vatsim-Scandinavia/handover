<?php

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
        Schema::table('users', function (Blueprint $table) {
            $table->string('pilot_rating_short', 6)->nullable()->after('pilot_rating');
            $table->string('pilot_rating_long', 24)->nullable()->after('pilot_rating_short');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pilot_rating_short');
            $table->dropColumn('pilot_rating_long');
        });
    }
};
