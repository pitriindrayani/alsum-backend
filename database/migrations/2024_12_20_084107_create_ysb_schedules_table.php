<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYsbSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ysb_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ysb_school_id')->nullable();
            $table->string('ysb_position_id')->nullable();
            $table->string('schedule_code')->nullable();
            $table->time('in_time')->nullable();
            $table->time('out_time')->nullable();
            $table->tinyInteger('day_1')->nullable();
            $table->tinyInteger('day_2')->nullable();
            $table->tinyInteger('day_3')->nullable();
            $table->tinyInteger('day_4')->nullable();
            $table->tinyInteger('day_5')->nullable();
            $table->tinyInteger('day_6')->nullable();
            $table->tinyInteger('day_7')->nullable();
            $table->tinyInteger('fg_school_default')->nullable();
            $table->string('holiday_type')->nullable();
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
        Schema::dropIfExists('ysb_schedules');
    }
}
