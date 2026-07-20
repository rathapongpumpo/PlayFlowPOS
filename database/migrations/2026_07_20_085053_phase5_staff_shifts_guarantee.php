<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Phase5StaffShiftsGuarantee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('staff_shifts', function (Blueprint $table) {
            $table->decimal('guarantee_amount', 10, 2)->default(0)->after('end_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('staff_shifts', function (Blueprint $table) {
            $table->dropColumn('guarantee_amount');
        });
    }
}

