<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnToYsbPeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ysb_periods', function (Blueprint $table) {
            $table->time('in_time')->nullable()->change(); 
            $table->time('out_time')->nullable()->change(); 
            $table->date('period_start_puasa')->nullable()->change(); 
            $table->date('period_end_puasa')->nullable()->change(); 
            $table->time('in_time_puasa')->nullable()->change(); 
            $table->time('out_time_puasa')->nullable()->change(); 
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
