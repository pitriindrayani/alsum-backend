<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYsbAttendanceDailysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ysb_attendance_dailys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ysb_teacher_id')->nullable();
            $table->string('ysb_branch_id')->nullable();
            $table->string('ysb_school_id')->nullable();
            $table->string('id_user_head_school')->nullable();
            $table->string('id_user_hr')->nullable();
            $table->tinyInteger('approve_hr')->nullable();
            $table->tinyInteger('approve_head_school')->nullable();
            $table->date('att_date')->nullable();
            $table->time('att_clock_in')->nullable();
            $table->time('att_clock_out')->nullable();
            $table->time('schedule_in')->nullable();
            $table->time('schedule_out')->nullable();
            $table->string('late_min')->nullable();
            $table->string('early_min')->nullable();
            $table->string('absent_type')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('kjm')->nullable();
            $table->string('ket1')->nullable();
            $table->string('telat_kurang_5')->nullable();
            $table->string('telat_lebih_5')->nullable();
            $table->string('pulang_kurang_5')->nullable();
            $table->string('pulang_lebih_5')->nullable();
            $table->string('jumlah_waktu')->nullable();
            $table->string('jam_lembur')->nullable();
            $table->string('absen1')->nullable();
            $table->tinyInteger('fg_locked')->nullable();
            $table->string('dokument')->nullable();
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
        Schema::dropIfExists('ysb_attendance_dailys');
    }
}
