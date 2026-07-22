<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePointSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('point_settings')) {
            Schema::create('point_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('branch_id')->default(0)->index();
                $table->decimal('earn_rate_thb', 10, 2)->default(100.00)->comment('กี่บาท = 1 แต้ม');
                $table->decimal('redeem_rate_thb', 10, 2)->default(1.00)->comment('1 แต้ม = กี่บาทส่วนลด');
                $table->decimal('min_spend_thb', 10, 2)->default(0.00)->comment('ยอดซื้อขั้นต่ำที่จะได้รับแต้ม');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Insert default row for branch 0 (Global Default)
            DB::table('point_settings')->insert([
                'branch_id' => 0,
                'earn_rate_thb' => 100.00,
                'redeem_rate_thb' => 1.00,
                'min_spend_thb' => 0.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('point_settings');
    }
}
