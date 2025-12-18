<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYsbSchoolsUkkConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ysb_schools_ukk_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ysb_branch_id')->nullable();
            $table->string('ysb_school_id')->nullable();
            $table->string('ysb_position_id')->nullable();
            $table->integer('kinerja')->nullable();
            $table->integer('ukk')->nullable();
            $table->integer('ukk_baru')->nullable();
            $table->integer('ut')->nullable();
            $table->integer('um')->nullable();
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
        Schema::dropIfExists('ysb_schools_ukk_configs');
    }
}
