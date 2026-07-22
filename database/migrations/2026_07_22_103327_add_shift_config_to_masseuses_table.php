<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShiftConfigToMasseusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('masseuses', function (Blueprint $table) {
            $table->time('shift_start')->nullable()->after('base_salary');
            $table->time('shift_end')->nullable()->after('shift_start');
            $table->decimal('guarantee_amount', 10, 2)->default(0)->after('shift_end');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('masseuses', function (Blueprint $table) {
            $table->dropColumn(['shift_start', 'shift_end', 'guarantee_amount']);
        });
    }
}
