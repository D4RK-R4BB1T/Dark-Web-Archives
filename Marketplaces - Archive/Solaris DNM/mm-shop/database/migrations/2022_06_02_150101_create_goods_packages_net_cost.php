<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsPackagesNetCost extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goods_packages', function (Blueprint $table) {
            $table->integer('net_cost')->default(null)->nullable(true)->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('goods_packages', function (Blueprint $table) {
            $table->dropColumn('net_cost');
        });
    }
}
