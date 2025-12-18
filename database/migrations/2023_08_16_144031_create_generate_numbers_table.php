<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGenerateNumbersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('generate_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->integer('order');
            $table->string('number');
            $table->boolean('available')->default(true);
            $table->dateTime('expire_at');
            $table->boolean('state')->default(true);
            $table->uuid('create_by');
            $table->uuid('update_by')->nullable();
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
        Schema::dropIfExists('generate_numbers');
    }
}
