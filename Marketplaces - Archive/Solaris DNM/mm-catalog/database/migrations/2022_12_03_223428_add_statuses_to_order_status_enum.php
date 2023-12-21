<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusesToOrderStatusEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TYPE orders_status ADD VALUE 'cancelled'");
        \DB::statement("ALTER TYPE orders_status ADD VALUE 'finished_after_dispute'");
        \DB::statement("ALTER TYPE orders_status ADD VALUE 'cancelled_after_dispute'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
