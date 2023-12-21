<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MoveGoodsCityIdToGoodsCities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $goods = \App\Good::all();
        foreach ($goods as $good) {
            \App\GoodsCity::create([
                'app_id' => $good->app_id,
                'app_good_id' => $good->app_good_id,
                'city_id' => $good->city_id
            ]);
        }

        Schema::table('goods', function (Blueprint $table) {
            $table->dropColumn('city_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('goods', function (Blueprint $table) {
            $table->integer('city_id')->nullable(false)->after('category_id');
        });
    }
}
