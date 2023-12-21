<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopsSecService extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('withdraw_shop_wallet')->default(true)->after('orders_chart_url');
            $table->string('disabled_reason')->default(null)->nullable()->after('withdraw_shop_wallet');
        });

        Schema::create('shop_overrides', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('shop_id')->unsigned()->index();
            $table->string('param', 64)->index();
            $table->string('value', 128);
            $table->unique(['shop_id', 'param']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('withdraw_shop_wallet');
            $table->dropColumn('disabled_reason');
        });

        Schema::dropIfExists('shop_overrides');
    }
}
