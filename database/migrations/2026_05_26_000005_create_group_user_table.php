<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_user', function (Blueprint $table) {
            $table->uuid('group_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('added_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->primary(['group_id', 'user_id']);
            $table->index('user_id');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_user');
    }
};
