<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnJoinDateYsbToYsbTeachersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ysb_teachers', function (Blueprint $table) {
            $table->date('join_date_ysb')->nullable()->after('join_date_ypi');
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
