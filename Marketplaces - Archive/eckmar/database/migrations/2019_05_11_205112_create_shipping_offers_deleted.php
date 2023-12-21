<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShippingOffersDeleted extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add deleted to offers
        Schema::table('offers', function (Blueprint $table) {
            $table->boolean('deleted')->default(false);
        });

        // add deleted to shippings
        Schema::table('shippings', function (Blueprint $table) {
            $table->boolean('deleted')->default(false);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipping_offers_deleted');
    }
}
