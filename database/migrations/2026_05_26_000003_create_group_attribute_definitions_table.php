<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_attribute_definitions', function (Blueprint $table) {
            $table->id();
            $keyCol = $table->string('key', 32)->unique();
            if (DB::getDriverName() === 'mysql') {
                $keyCol->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            }
            $table->string('label', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_attribute_definitions');
    }
};
