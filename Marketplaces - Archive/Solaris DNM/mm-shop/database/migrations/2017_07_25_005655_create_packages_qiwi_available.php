<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackagesQiwiAvailable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goods_packages', function (Blueprint $table) {
            $table->boolean('qiwi_enabled')->default(false)->after('currency');
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
            $table->dropColumn('qiwi_enabled');
        });
    }
}
