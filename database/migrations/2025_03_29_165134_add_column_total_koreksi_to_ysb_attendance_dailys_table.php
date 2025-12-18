<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnTotalKoreksiToYsbAttendanceDailysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ysb_attendance_dailys', function (Blueprint $table) {
            $table->integer('total_koreksi')->nullable()->after('tipe_koreksi');
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
    