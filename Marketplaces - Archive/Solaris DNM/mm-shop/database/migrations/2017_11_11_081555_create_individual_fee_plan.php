<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIndividualFeePlan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement(
            DB::raw("ALTER TABLE `shops` CHANGE `plan` `plan` ENUM('basic', 'advanced', 'individual', 'fee', 'individual_fee') DEFAULT 'basic' NOT NULL")
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
            DB::raw("ALTER TABLE `shops` CHANGE `plan` `plan` ENUM('basic', 'advanced', 'individual', 'fee') DEFAULT 'basic' NOT NULL")
        );
    }
}
