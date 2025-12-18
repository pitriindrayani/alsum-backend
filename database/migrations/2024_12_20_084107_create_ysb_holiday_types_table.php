<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYsbHolidayTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ysb_holiday_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('holiday_type')->nullable();
            $table->string('holiday_index')->nullable();
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
        Schema::dropIfExists('ysb_holiday_types');
    }
}
