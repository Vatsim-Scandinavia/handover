<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->uuid('group_id');
            $table->unsignedBigInteger('attribute_definition_id');
            $table->string('value', 255);
            $table->unique(['group_id', 'attribute_definition_id']);
            $table->index(['attribute_definition_id', 'value']);
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('attribute_definition_id')
                ->references('id')->on('group_attribute_definitions')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_attribute_values');
    }
};
