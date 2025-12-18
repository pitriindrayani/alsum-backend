<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToYsbScheduleTeachersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ysb_schedule_teachers', function (Blueprint $table) {
            $table->tinyInteger('update_arrive')->nullable()->after('out_time');
            $table->tinyInteger('update_late')->nullable()->after('update_arrive');
            $table->tinyInteger('update_duration')->nullable()->after('update_late');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ysb_schedule_teachers', function (Blueprint $table) {
            //
        });
    }
}
    