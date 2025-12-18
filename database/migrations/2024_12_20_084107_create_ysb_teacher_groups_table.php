<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYsbTeacherGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ysb_teacher_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('rank_code')->nullable();
            $table->string('rank_group')->nullable();
            $table->string('rank_room')->nullable();
            $table->string('rank_index')->nullable();
            $table->string('rank_name')->nullable();
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
        Schema::dropIfExists('ysb_teacher_groups');
    }
}
