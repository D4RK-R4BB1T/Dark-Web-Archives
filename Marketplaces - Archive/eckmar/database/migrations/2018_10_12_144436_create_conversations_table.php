<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id');
            $table -> uuid('sender_id') -> nullable(); // NULL is when it is an mass message
            $table -> uuid('receiver_id');
            $table->timestamps();

            $table -> primary('id');
            $table -> foreign('sender_id') -> references('id') -> on('users') -> onDelete('cascade');
            $table -> foreign('receiver_id') -> references('id') -> on('users') -> onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversations');
    }
}
