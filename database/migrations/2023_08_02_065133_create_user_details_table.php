<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('unique_id')->unique();
            $table->uuid('id_user');
            $table->uuid('id_parent')->nullable();
            $table->string('firstname');
            $table->string('lastname')->nullable();
            $table->text('address');
            $table->string('phone_number');
            $table->string('birth_place')->nullable();
            $table->date('birth_day')->nullable();
            $table->string('gender');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_details');
    }
}
