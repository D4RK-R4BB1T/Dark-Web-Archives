<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MoveGoodsToCities extends Migration
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
                'good_id' => $good->id,
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
        //
    }
}
