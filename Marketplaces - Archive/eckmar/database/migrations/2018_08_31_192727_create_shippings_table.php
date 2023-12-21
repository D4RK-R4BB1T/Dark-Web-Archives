<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShippingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shippings', function (Blueprint $table) {
            $table->uuid('id');
            $table -> uuid('product_id');
            $table -> string('name');
            $table -> decimal('price', 16,2);
            $table -> string('duration', 30);
            $table -> integer('from_quantity');
            $table -> integer('to_quantity');
            $table->timestamps();
            // key id
            $table -> primary('id');
            $table -> foreign('product_id') -> references('id') -> on('products') -> onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shippings');
    }
}
