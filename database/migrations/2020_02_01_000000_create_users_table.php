<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->unsignedInteger('id')->primary();
            $table->string('email', 64);

            $table->string('first_name');
            $table->string('last_name');

            $table->tinyInteger('rating');
            $table->string('rating_short', 3);
            $table->string('rating_long', 24);

            $table->string('pilot_rating', 4);

            $table->string('country', 2)->nullable();
            $table->string('region', 8);
            $table->string('division', 3)->nullable();
            $table->string('subdivision', 3)->nullable();

            $table->tinyInteger('atc_active')->nullable();
            $table->tinyInteger('visiting_controller')->nullable();
            $table->tinyInteger('accepted_privacy');

            $table->rememberToken();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->unsignedBigInteger('token_expires')->nullable();

            $table->timestamp('last_login')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
