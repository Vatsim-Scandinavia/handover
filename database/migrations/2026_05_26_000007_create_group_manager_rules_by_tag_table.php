<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_manager_rules_by_tag', function (Blueprint $table) {
            $table->id();
            $table->uuid('manager_group_id');
            $table->string('target_tag', 32);
            $table->unique(['manager_group_id', 'target_tag'], 'gmr_by_tag_unique');
            $table->timestamps();
            $table->foreign('manager_group_id')->references('id')->on('groups')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_manager_rules_by_tag');
    }
};
