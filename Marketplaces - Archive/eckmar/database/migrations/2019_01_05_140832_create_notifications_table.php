<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('user_id');
            $table->mediumText('description');
            $table->boolean('read')->default(false);
            $table->text('route_name')->nullable();
            $table->mediumText('route_params')->nullable();
            $table->timestamps();
            $table -> primary('id');
            $table -> foreign('user_id') -> references('id') -> on('users') -> onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
