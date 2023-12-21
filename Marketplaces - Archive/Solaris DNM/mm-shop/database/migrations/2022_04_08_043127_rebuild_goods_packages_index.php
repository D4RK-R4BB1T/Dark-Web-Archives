<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RebuildGoodsPackagesIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goods_packages', function (Blueprint $table) {
            $table->dropIndex('shop_id');
            $table->unique(['shop_id', 'good_id', 'city_id', 'amount', 'measure', 'preorder'], 'unique_package');
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

