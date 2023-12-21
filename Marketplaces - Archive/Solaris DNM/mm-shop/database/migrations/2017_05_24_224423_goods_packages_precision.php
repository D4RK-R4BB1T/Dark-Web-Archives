<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class GoodsPackagesPrecision extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::getConnection()->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        Schema::table('goods_packages', function (Blueprint $table) {
            $table->decimal('amount', 16, 8)->change();
            $table->decimal('price', 16, 8)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('package_amount', 16, 8)->change();
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
            //
        });
    }
}
