<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsCitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_cities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('app_id', 255)->nullable(false)->index();
            $table->integer('app_good_id')->nullable(false)->index();
            $table->integer('city_id')->nullable(false)->index();

            $table->index(['app_id', 'app_good_id']);
            $table->unique(['app_id', 'app_good_id', 'city_id'], 'all_columns_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_cities');
    }
}
