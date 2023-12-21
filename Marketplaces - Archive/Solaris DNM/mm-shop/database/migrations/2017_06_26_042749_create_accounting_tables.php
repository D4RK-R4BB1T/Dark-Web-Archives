<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountingTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounting_lots', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('shop_id');
            $table->integer('good_id');
            $table->double('amount', 16, 8);
            $table->double('unused_amount', 16, 8);
            $table->double('available_amount', 16, 8);
            $table->enum('measure', [
                \App\GoodsPackage::MEASURE_GRAM,
                \App\GoodsPackage::MEASURE_ML,
                \App\GoodsPackage::MEASURE_PIECE
            ]);
            $table->double('price', 16, 8);
            $table->enum('currency', [
                \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC,
                \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB,
                \App\Packages\Utils\BitcoinUtils::CURRENCY_USD,
            ]);
            $table->mediumText('note')->nullable();
            $table->timestamps();

            $table->index('shop_id');
            $table->index(['shop_id', 'good_id']);
            $table->index(['shop_id', 'created_at']);
            $table->index(['shop_id', 'good_id', 'created_at']);
        });

        Schema::create('accounting_distributions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lot_id');
            $table->integer('employee_id');
            $table->double('amount', 16, 8);
            $table->double('available_amount', 16, 8);
            $table->double('proceed_btc', 16, 8);
            $table->mediumText('note')->nullable();
            $table->timestamps();

            $table->index('lot_id');
            $table->index(['lot_id', 'employee_id']);
        });

        Schema::table('goods_positions', function (Blueprint $table) {
            $table->integer('distribution_id')->nullable()->default(null)->after('package_id');
            $table->index('distribution_id');
            $table->index(['distribution_id', 'available']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('accounting_lots');
        Schema::drop('accounting_distributions');
        Schema::table('goods_positions', function (Blueprint $table) {
            $table->dropColumn('distribution_id');
        });
    }
}
