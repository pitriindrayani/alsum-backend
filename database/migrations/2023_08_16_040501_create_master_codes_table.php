<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('start_by');
            $table->string('first_code');
            $table->boolean('need_initial')->default(false);
            $table->string('initial_by')->nullable();
            $table->string('seperate_by');
            $table->string('month_type');
            $table->string('reset_type');
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
        Schema::dropIfExists('master_codes');
    }
}
