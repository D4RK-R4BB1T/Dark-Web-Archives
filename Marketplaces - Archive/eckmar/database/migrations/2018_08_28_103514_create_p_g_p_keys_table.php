<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePGPKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pgpkeys', function (Blueprint $table) {
            $table->uuid('id');
            $table-> uuid('user_id');
            $table -> text('key');

            $table->timestamps();
            $table-> primary('id');
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
        Schema::dropIfExists('pgpkeys');
    }
}
