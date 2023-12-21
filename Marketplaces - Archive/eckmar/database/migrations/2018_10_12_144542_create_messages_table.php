<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id');
            $table -> longText('content_sender');
            $table -> longText('nonce_sender');
            $table -> longText('content_receiver');
            $table -> longText('nonce_receiver');
            $table -> uuid('conversation_id');
            $table -> uuid('sender_id') -> nullable(); // null is when it is from the market
            $table -> uuid('receiver_id');
            $table -> boolean('read') ->default(false);
            $table->timestamps();

            $table -> primary('id');
            $table -> foreign('conversation_id') -> references('id') -> on('conversations') -> onDelete('cascade');
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
        Schema::dropIfExists('messages');
    }
}
