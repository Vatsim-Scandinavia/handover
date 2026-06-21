<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_manager_rules_by_attribute', function (Blueprint $table) {
            $table->id();
            $table->uuid('manager_group_id');
            $keyCol = $table->string('target_attribute_key', 32);
            if (DB::getDriverName() === 'mysql') {
                $keyCol->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            }
            $table->string('target_attribute_value', 255);
            $table->unique(['manager_group_id', 'target_attribute_key', 'target_attribute_value'], 'gmr_by_attr_unique');
            $table->timestamps();
            $table->foreign('manager_group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('target_attribute_key')
                ->references('key')
                ->on('group_attribute_definitions')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_manager_rules_by_attribute');
    }
};
