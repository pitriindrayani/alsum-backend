<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToYsbAttendanceDailysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ysb_attendance_dailys', function (Blueprint $table) {
            $table->tinyInteger('update')->nullable()->after('approve_by_hr');
            $table->tinyInteger('update_arrive')->nullable()->after('update');
            $table->tinyInteger('update_late')->nullable()->after('update_arrive');
            $table->tinyInteger('update_absen1x')->nullable()->after('update_late');
            $table->tinyInteger('update_kehadiran')->nullable()->after('update_absen1x');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ysb_attendance_dailys', function (Blueprint $table) {
            //
        });
    }
}
    