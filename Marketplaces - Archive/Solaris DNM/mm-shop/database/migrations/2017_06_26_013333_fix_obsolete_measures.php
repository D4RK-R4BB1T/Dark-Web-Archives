<?php

use App\GoodsPackage;
use App\Order;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixObsoleteMeasures extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::getConnection()->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        foreach (GoodsPackage::whereIn('measure', ['mg', 'kg'])->get() as $package) {
            $package->measure = GoodsPackage::MEASURE_GRAM;
            $package->amount = ($package->measure === 'kg')
                ? $package->amount * 1000
                : $package->amount * 0.001;
            $package->save();
        }

        foreach (Order::whereIn('package_measure', ['mg', 'kg'])->get() as $order) {
            $order->package_measure = GoodsPackage::MEASURE_GRAM;
            $order->package_amount = ($order->package_measure === 'kg')
                ? $order->package_amount * 1000
                : $order->package_amount * 0.001;
            $order->save();
        }

        DB::statement("ALTER TABLE goods_packages CHANGE COLUMN measure measure ENUM('gr', 'piece', 'ml') NOT NULL");
        DB::statement("ALTER TABLE orders CHANGE COLUMN package_measure package_measure ENUM('gr', 'piece', 'ml') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
