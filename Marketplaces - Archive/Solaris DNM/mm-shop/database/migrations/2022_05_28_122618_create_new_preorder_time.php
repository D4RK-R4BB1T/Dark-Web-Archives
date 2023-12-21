<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewPreorderTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE goods_packages MODIFY preorder_time ENUM('24', '48', '72', '480')");
        DB::statement("ALTER TABLE orders MODIFY package_preorder_time ENUM('24', '48', '72', '480')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE goods_packages MODIFY preorder_time ENUM('24', '48', '72')");
        DB::statement("ALTER TABLE orders MODIFY package_preorder_time ENUM('24', '48', '72')");
    }
}
