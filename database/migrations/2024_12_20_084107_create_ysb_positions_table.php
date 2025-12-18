<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYsbPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ysb_positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('position_code')->nullable();
            $table->string('position_category')->nullable();
            $table->string('position')->nullable();
            $table->string('slug_name')->nullable();
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
        Schema::dropIfExists('ysb_positions');
    }
}
