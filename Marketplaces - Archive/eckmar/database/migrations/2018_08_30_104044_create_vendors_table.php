<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVendorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->uuid('id');
            $table->unsignedInteger('vendor_level');
            $table->integer('experience')->default(0);
            $table->text('about')->nullable();
            $table->text('profilebg')->nullable();
            $table->boolean('trusted')->default(false);
            $table->timestamps();
            $table -> primary('id');
            $table -> foreign('id') -> references('id') -> on('users') -> onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vendors');
    }
}
