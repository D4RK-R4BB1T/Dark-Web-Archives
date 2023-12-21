<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackagesHasReadyQuests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goods_packages', function (Blueprint $table) {
            $table->tinyInteger('has_quests')->default(0)->after('employee_penalty');
            $table->tinyInteger('has_ready_quests')->default(0)->after('has_quests');
            $table->index(['has_quests', 'has_ready_quests']);
        });


        Schema::table('goods', function (Blueprint $table) {
            $table->dropColumn(['has_quests', 'has_ready_quests']);
        });

        Artisan::call('mm2:update_has_quests_cache');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('goods', function (Blueprint $table) {
            $table->tinyInteger('has_quests')->default(0)->after('image_url');
            $table->tinyInteger('has_ready_quests')->default(0)->after('has_quests');
            $table->index(['has_quests', 'has_ready_quests']);
        });

        Schema::table('goods_packages', function (Blueprint $table) {
            $table->dropColumn(['has_quests', 'has_ready_quests']);
        });
    }
}
