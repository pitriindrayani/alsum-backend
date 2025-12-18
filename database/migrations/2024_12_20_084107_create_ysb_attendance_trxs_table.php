<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYsbAttendanceTrxsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ysb_attendance_trxs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('id_attendance')->nullable();
            $table->tinyInteger('att_method')->nullable();
            $table->string('att_date')->nullable();
            $table->time('att_time')->nullable();
            $table->string('finger_id')->nullable();
            $table->string('description')->nullable();
            $table->tinyInteger('att_type')->nullable();
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
        Schema::dropIfExists('ysb_attendance_trxs');
    }
}
