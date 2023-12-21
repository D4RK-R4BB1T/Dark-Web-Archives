<?php

use Illuminate\Database\Migrations\Migration;

class ChangeOrdersPackagePreorderTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $goods_packages_old_values = [];
        $orders_old_values = [];

        \App\GoodsPackage::select(['id', 'preorder_time'])->where('preorder', '=', '1')->get()->each(function ($row) use (&$goods_packages_old_values) {
            $goods_packages_old_values[$row->id] = $row->preorder_time;
        });

         \DB::statement("ALTER TABLE `goods_packages` CHANGE `preorder_time` `preorder_time` SMALLINT NULL DEFAULT NULL");

         foreach ($goods_packages_old_values as $id=>$v) {
             if($goods_package = \App\GoodsPackage::find($id)) {
                 $goods_package->preorder_time = $v;
                 $goods_package->save();
             }
         }

         unset($goods_packages_old_values);

         \App\Order::select(['id', 'package_preorder_time'])->where('package_preorder', '=', '1')->get()->each(function ($row) use (&$orders_old_values) {
             $orders_old_values[$row->id] = $row->package_preorder_time;
         });

         \DB::statement("ALTER TABLE `orders` CHANGE `package_preorder_time` `package_preorder_time` SMALLINT NULL DEFAULT NULL");

         foreach ($orders_old_values as $id=>$v) {
            if($order = \App\Order::find($id)) {
                $order->package_preorder_time = $v;
                $order->save();
            }
         }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
