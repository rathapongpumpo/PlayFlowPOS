<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class Phase3WalletPointsCrm extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Add wallet_balance to customers
        if (!Schema::hasColumn('customers', 'wallet_balance')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->decimal('wallet_balance', 10, 2)->default(0.00)->after('total_points');
            });
        }

        // 2. Create wallet_transactions table
        if (!Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('branch_id');
            $table->bigInteger('customer_id');
            $table->enum('type', ['topup', 'spend', 'refund']);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->bigInteger('order_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
        }

        // 3. Add points_earned, points_redeemed to orders
        if (!Schema::hasColumn('orders', 'points_earned')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->integer('points_earned')->default(0)->after('grand_total');
                $table->integer('points_redeemed')->default(0)->after('points_earned');
            });
        }

        // 4. Create point_transactions table
        if (!Schema::hasTable('point_transactions')) {
            Schema::create('point_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('branch_id');
            $table->bigInteger('customer_id');
            $table->enum('type', ['earn', 'redeem', 'adjust']);
            $table->integer('points');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->bigInteger('order_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
        }

        // 5. Update orders.payment_method to include wallet
        // Because doctrine DBAL sometimes struggles with enum updates, using raw SQL
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cash', 'transfer', 'credit_card', 'package_redeem', 'wallet') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('cash', 'transfer', 'credit_card', 'package_redeem') NOT NULL");
        
        Schema::dropIfExists('point_transactions');
        
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['points_earned', 'points_redeemed']);
        });
        
        Schema::dropIfExists('wallet_transactions');
        
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('wallet_balance');
        });
    }
}
