<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnNipYpiKaryawanToYsbTeachersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ysb_teachers', function (Blueprint $table) {
            $table->string('nip_ypi_karyawan')->nullable()->after('nip_ypi');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ysb_teachers', function (Blueprint $table) {
            //
        });
    }
}
