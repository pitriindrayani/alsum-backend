<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToYsbPeriodHolidaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ysb_period_holidays', function (Blueprint $table) {
            $table->string('holiday_type')->nullable()->after('holiday_date');
            $table->string('description')->nullable()->after('holiday_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ysb_period_holidays', function (Blueprint $table) {
            //
        });
    }
}
    