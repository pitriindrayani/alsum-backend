<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYsbSchoolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ysb_schools', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ysb_branch_id')->nullable();
            $table->string('school_name')->nullable();
            $table->string('slug_name')->nullable();
            $table->string('npsn')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('subdistrict')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('edu_stage')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('school_logo')->nullable();
            $table->string('nss')->nullable();
            $table->string('village')->nullable();
            $table->string('footer_school_name')->nullable();
            $table->string('akreditasi')->nullable();
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
        Schema::dropIfExists('ysb_schools');
    }
}
