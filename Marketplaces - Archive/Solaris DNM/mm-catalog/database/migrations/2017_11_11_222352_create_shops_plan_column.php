<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopsPlanColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->enum('plan', [
                \App\Shop::PLAN_BASIC,
                \App\Shop::PLAN_ADVANCED,
                \App\Shop::PLAN_INDIVIDUAL,
                \App\Shop::PLAN_FEE,
                \App\Shop::PLAN_INDIVIDUAL_FEE
            ])->nullable()->default(null)->after('title');
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
            $table->dropColumn('plan');
        });
    }
}
