<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnKoreksiToYsbAttendanceDailysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ysb_attendance_dailys', function (Blueprint $table) {
            $table->string('tipe_koreksi')->nullable()->after('absent_type');
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
    