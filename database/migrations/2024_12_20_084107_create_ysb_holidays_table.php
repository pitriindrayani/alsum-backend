<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYsbHolidaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ysb_holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ysb_school_id')->nullable();
            $table->string('holiday_name')->nullable();
            $table->date('holiday_date')->nullable();
            $table->integer('holiday_weekday')->nullable();
            $table->string('holiday_type_id')->nullable();
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
        Schema::dropIfExists('ysb_holidays');
    }
}
