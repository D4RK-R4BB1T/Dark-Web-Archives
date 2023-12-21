<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offers', function (Blueprint $table) {
            $table ->uuid('id');
            $table ->uuid('product_id');
            $table ->integer('min_quantity');
            $table ->decimal('price', 16, 2); // in cents
            $table->timestamps();

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
        Schema::dropIfExists('offers');
    }
}
