<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterOrdersStatusReserved extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement(
            DB::raw("ALTER TABLE `orders` CHANGE `status` `status` ENUM('preorder_paid','paid','problem','finished','qiwi_reserved','qiwi_paid') NOT NULL")
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement(
            DB::raw("ALTER TABLE `orders` CHANGE `status` `status` ENUM('preorder_paid','paid','problem','finished','reserved','qiwi_paid') NOT NULL")
        );
    }
}
