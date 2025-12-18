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
            $table->timestamp('approve_at_head')->nullable()->after('dokument');
            $table->string('approve_by_head')->nullable()->after('approve_at_head');
            $table->timestamp('approve_at_hr')->nullable()->after('approve_by_head');
            $table->string('approve_by_hr')->nullable()->after('approve_at_hr');
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
    