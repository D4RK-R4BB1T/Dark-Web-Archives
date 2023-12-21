<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsPackagesHasQuests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goods_packages', function (Blueprint $table) {
            $table->tinyInteger('has_quests')->default(0)->after('preorder');
            $table->tinyInteger('has_ready_quests')->default(0)->after('has_quests');
            $table->index(['has_quests', 'has_ready_quests']);
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
            $table->dropColumn(['has_quests', 'has_ready_quests']);
        });
    }
}
