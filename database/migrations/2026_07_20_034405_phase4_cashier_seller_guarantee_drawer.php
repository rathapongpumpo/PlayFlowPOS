<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Phase4CashierSellerGuaranteeDrawer extends Migration
{
    public function up()
    {
        // 1. Add seller_id to orders
        if (!Schema::hasColumn('orders', 'seller_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->bigInteger('seller_id')->nullable()->after('customer_id');
            });
        }

        // 2. Add daily_guarantee to users (assuming users holds staff records)
        if (!Schema::hasColumn('users', 'daily_guarantee')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('daily_guarantee', 10, 2)->default(0)->after('role');
            });
        }

        // 3. Create cash_drawers table for Open/Close shift
        if (!Schema::hasTable('cash_drawers')) {
            Schema::create('cash_drawers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('branch_id');
                $table->bigInteger('opened_by'); // User ID who opened
                $table->bigInteger('closed_by')->nullable(); // User ID who closed
                $table->decimal('opening_amount', 10, 2)->default(0);
                $table->decimal('closing_amount', 10, 2)->nullable();
                $table->decimal('expected_amount', 10, 2)->nullable();
                $table->decimal('difference', 10, 2)->nullable();
                $table->text('note')->nullable();
                $table->timestamp('opened_at')->useCurrent();
                $table->timestamp('closed_at')->nullable();
            });
        }

        // 4. Create store_assets table for Internal Inventory
        if (!Schema::hasTable('store_assets')) {
            Schema::create('store_assets', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('branch_id');
                $table->string('name');
                $table->integer('qty')->default(0);
                $table->string('unit')->default('ชิ้น');
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        // 5. Create store_asset_transactions table
        if (!Schema::hasTable('store_asset_transactions')) {
            Schema::create('store_asset_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('asset_id');
                $table->bigInteger('user_id');
                $table->enum('type', ['add', 'remove', 'audit']);
                $table->integer('qty');
                $table->integer('balance_after');
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        // 6. Create customer_stamps table
        if (!Schema::hasTable('customer_stamps')) {
            Schema::create('customer_stamps', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('branch_id');
                $table->bigInteger('customer_id');
                $table->bigInteger('order_id')->nullable();
                $table->enum('type', ['earn', 'redeem']);
                $table->integer('stamps');
                $table->integer('balance_after');
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        // Add total_stamps to customers
        if (!Schema::hasColumn('customers', 'total_stamps')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->integer('total_stamps')->default(0)->after('wallet_balance');
            });
        }
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('total_stamps');
        });

        Schema::dropIfExists('customer_stamps');
        Schema::dropIfExists('store_asset_transactions');
        Schema::dropIfExists('store_assets');
        Schema::dropIfExists('cash_drawers');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('daily_guarantee');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('seller_id');
        });
    }
}
