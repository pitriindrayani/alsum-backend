<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYsbTeachersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ysb_teachers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nip_ypi')->nullable();
            $table->string('nik_ysb')->nullable();
            $table->date('join_date_ypi')->nullable();
            $table->string('full_name')->nullable();
            $table->string('nik_ktp')->nullable();
            $table->string('birthplace')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('gender')->nullable();
            $table->string('employment_status')->nullable();
            $table->string('ysb_branch_id')->nullable();
            $table->string('edu_stage')->nullable();
            $table->string('ysb_school_id')->nullable();
            $table->string('ysb_position_id')->nullable();
            $table->string('ysb_schedule_id')->nullable();
            $table->string('bidang')->nullable();
            $table->string('ysb_teacher_group_id')->nullable();
            $table->string('religion')->nullable();
            $table->string('addrees')->nullable();
            $table->string('dom_address')->nullable();
            $table->string('marriage')->nullable();
            $table->string('npwp')->nullable();
            $table->string('ptkp')->nullable();
            $table->string('university')->nullable();
            $table->string('major')->nullable();
            $table->string('degree')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('bank')->nullable();
            $table->string('nama_rekening')->nullable();
            $table->string('no_rekening')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_relation')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('nuptk')->nullable();
            $table->string('user_id')->nullable();
            $table->string('zakat')->nullable();
            $table->tinyInteger('fg_active')->nullable();
            $table->string('finger_id')->nullable();
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
        Schema::dropIfExists('ysb_teachers');
    }
}
