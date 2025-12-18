<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYsbScheduleTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ysb_schedule_times', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ysb_id_teacher')->nullable();
            $table->string('ysb_branch_id')->nullable();
            $table->string('ysb_school_id')->nullable();
            $table->string('full_name')->nullable();
            $table->date('date_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->string('description')->nullable();
            $table->boolean('state')->default(true);
            $table->uuid('create_by')->nullable();
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
        Schema::dropIfExists('ysb_schedule_times');
    }
}
