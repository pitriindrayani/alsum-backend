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
            $table->time('in_time')->nullable()->after('approve_head_school');
            $table->time('out_time')->nullable()->after('in_time');
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
    