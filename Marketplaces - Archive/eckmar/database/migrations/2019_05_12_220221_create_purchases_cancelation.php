<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePurchasesCancelation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $stateQutoed = array_map(function($state){
            return "'$state'";
        }, array_keys(\App\Purchase::$states));
        $statesStringinfied = implode(",", $stateQutoed);

        // custom statment to add enum value to states of the purchases
        DB::statement("ALTER TABLE purchases MODIFY COLUMN state ENUM($statesStringinfied) DEFAULT 'purchased' NOT NULL" );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
