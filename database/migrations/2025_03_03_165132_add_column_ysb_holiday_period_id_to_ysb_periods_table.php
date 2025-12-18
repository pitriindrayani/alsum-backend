<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnYsbHolidayPeriodIdToYsbPeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ysb_periods', function (Blueprint $table) {
            $table->date('ysb_period_holiday_id')->nullable()->after('out_time_puasa');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ysb_periods', function (Blueprint $table) {
            //
        });
    }
}
