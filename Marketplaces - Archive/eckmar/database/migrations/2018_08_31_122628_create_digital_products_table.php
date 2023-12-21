<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDigitalProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('digital_products', function (Blueprint $table) {
            $table->uuid('id');
            // digital delivery field
            $table -> boolean('autodelivery') ->default(true);
            $table -> boolean('unlimited') ->default(true);
            $table -> text('content') -> nullable(); // content for autodelivery
            $table->timestamps();

            // keys
            $table -> primary('id');
            $table -> foreign('id') -> references('id') -> on('products') -> onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('digital_products');
    }
}
