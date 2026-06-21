<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_tags', function (Blueprint $table) {
            $table->uuid('group_id');
            $table->string('tag', 32);
            $table->primary(['group_id', 'tag']);
            $table->index('tag');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_tags');
    }
};
