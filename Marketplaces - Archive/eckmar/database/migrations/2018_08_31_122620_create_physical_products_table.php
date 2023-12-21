<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhysicalProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('physical_products', function (Blueprint $table) {
            $table->uuid('id');
            // Shipping info
            $table -> enum('countries_option', array_keys(\App\PhysicalProduct::$countriesOptions)) ->default('all')->nullable();
            $table -> text('countries');
            $table -> string('country_from') -> nullable();

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
        Schema::dropIfExists('physical_products');
    }
}
