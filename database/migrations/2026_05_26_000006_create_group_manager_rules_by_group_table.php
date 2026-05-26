<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_manager_rules_by_group', function (Blueprint $table) {
            $table->id();
            $table->uuid('manager_group_id');
            $table->uuid('target_group_id');
            $table->unique(['manager_group_id', 'target_group_id'], 'gmr_by_group_unique');
            $table->timestamps();
            $table->foreign('manager_group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('target_group_id')->references('id')->on('groups')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_manager_rules_by_group');
    }
};
