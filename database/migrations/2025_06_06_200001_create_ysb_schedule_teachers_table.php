<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYsbScheduleTeachersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ysb_schedule_teachers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('year')->nullable();
            $table->string('month')->nullable();
            $table->string('ysb_branch_id')->nullable();
            $table->string('ysb_school_id')->nullable();
            $table->string('ysb_id_teacher')->nullable();
            $table->string('full_name')->nullable();
            $table->date('date')->nullable();
            $table->tinyInteger('day_libur')->nullable();
            $table->string('day_keterangan')->nullable();
            $table->time('in_time')->nullable();
            $table->time('out_time')->nullable();
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
        Schema::dropIfExists('ysb_schedule_teachers');
    }
}
