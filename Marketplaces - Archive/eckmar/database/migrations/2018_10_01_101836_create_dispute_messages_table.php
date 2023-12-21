<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDisputeMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dispute_messages', function (Blueprint $table) {
            $table -> uuid('id');
            $table -> text('message');
            $table -> uuid('author_id');
            $table -> uuid('dispute_id');
            $table -> timestamps();

            $table -> primary('id');
            $table -> foreign('author_id') -> references('id') -> on('users') -> onDelete('cascade');
            $table -> foreign('dispute_id') -> references('id') -> on('disputes') -> onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dispute_messages');
    }
}
