<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToYsbPeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ysb_periods', function (Blueprint $table) {
            $table->string('in_time')->nullable()->after('period_end');
            $table->string('out_time')->nullable()->after('in_time');
            $table->string('period_start_puasa')->nullable()->after('out_time');
            $table->string('period_end_puasa')->nullable()->after('period_start_puasa');
            $table->string('in_time_puasa')->nullable()->after('period_end_puasa');
            $table->string('out_time_puasa')->nullable()->after('in_time_puasa');
            $table->string('out_time_puasa')->nullable()->after('in_time_puasa');
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
