<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYsbWfhTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ysb_wfh', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ysb_teacher_id')->nullable();
            $table->string('ysb_branch_id')->nullable();
            $table->string('ysb_school_id')->nullable();
            $table->string('id_user_head_school')->nullable();
            $table->string('full_name')->nullable();
            $table->time('schedule_in')->nullable();
            $table->time('schedule_out')->nullable();
            $table->tinyInteger('approve_hr')->nullable();
            $table->tinyInteger('approve_head_school')->nullable();
            $table->string('keterangan')->nullable();
            $table->string('dokument')->nullable();
            $table->timestamp('approve_at_head')->nullable();
            $table->string('approve_by_head')->nullable();
            $table->timestamp('approve_at_hr')->nullable();
            $table->string('approve_by_hr')->nullable();
            $table->date('att_date')->nullable();
            $table->time('att_clock_in')->nullable();
            $table->time('att_clock_out')->nullable();
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
        Schema::dropIfExists('ysb_wfh');
    }
}
