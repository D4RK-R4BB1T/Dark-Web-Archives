<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserRoleTelegram extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement(
            DB::raw("ALTER TABLE `users` CHANGE `role` `role` ENUM('admin','user','shop','shop_pending','catalog','telegram') NOT NULL")
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
            DB::raw("ALTER TABLE `users` CHANGE `role` `role` ENUM('admin','user','shop','shop_pending','catalog') NOT NULL")
        );
    }
}
