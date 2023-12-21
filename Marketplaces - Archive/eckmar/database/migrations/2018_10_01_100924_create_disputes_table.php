<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDisputesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table -> uuid('id');
            $table -> uuid('winner_id') -> nullable();
            $table -> timestamps();

            $table -> primary('id');
            $table -> foreign('winner_id') -> references('id') -> on('users') -> onDelete('set null'); // set null when winner is deleted
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('disputes');
    }
}
