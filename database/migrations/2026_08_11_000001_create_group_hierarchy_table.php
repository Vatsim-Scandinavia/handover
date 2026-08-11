<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_hierarchy', function (Blueprint $table) {
            $table->uuid('parent_id');
            $table->uuid('child_id');
            $table->primary(['parent_id', 'child_id']);
            $table->index('child_id');
            $table->foreign('parent_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('child_id')->references('id')->on('groups')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_hierarchy');
    }
};
