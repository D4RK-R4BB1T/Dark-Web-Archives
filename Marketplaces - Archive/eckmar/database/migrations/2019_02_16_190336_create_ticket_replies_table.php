<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketRepliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->uuid('id');
            $table -> uuid('user_id');
            $table -> uuid('ticket_id');
            $table -> text('text');

            $table->timestamps();

            $table -> primary('id');
            $table -> foreign('user_id') -> references('id') -> on('users') -> onDelete('cascade');
            $table -> foreign('ticket_id') -> references('id') -> on('tickets') -> onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_replies');
    }
}
