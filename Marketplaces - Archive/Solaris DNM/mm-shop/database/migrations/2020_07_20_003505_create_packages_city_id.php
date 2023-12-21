<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackagesCityId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goods_packages', function (Blueprint $table) {
            $table->integer('city_id')->after('good_id')->default(0);
            $table->index('city_id');
            $table->index(['good_id', 'city_id']);
        });

        $packages = \App\GoodsPackage::with(['good', 'good.cities'])->get();
        foreach ($packages as $package) {
            $good = $package->good;
            $city = $good->cities->first();
            $package->city_id = $city->id;
            $package->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('goods_packages', function (Blueprint $table) {
            //
        });
    }
}
