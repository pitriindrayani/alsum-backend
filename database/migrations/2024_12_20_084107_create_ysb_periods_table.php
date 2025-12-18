<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYsbPeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ysb_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('period_title')->nullable();
            $table->integer('year')->nullable();
            $table->integer('month')->nullable();
            $table->date('period_start')->nullable();
            $table->integer('period_start_weekday')->nullable();
            $table->date('period_end')->nullable();
            $table->integer('period_end_weekday')->nullable();
            $table->integer('days')->nullable();
            $table->string('alazhar_title')->nullable();
            $table->string('alazhar_pic')->nullable();
            $table->integer('fg_active')->nullable();
            $table->boolean('state')->default(true);
            $table->uuid('create_by')->nullable();
            $table->uuid('update_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ysb_periods');
    }
}
