<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVendorPurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendor_purchases', function (Blueprint $table) {
            $table->uuid('id');
            $table -> uuid('user_id');
            $table -> text('address');
            $table -> string('coin', 10);
            $table -> decimal('amount', 24,12)->default(0); // amount on the address
            $table -> timestamps();

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
        Schema::dropIfExists('vendor_purchases');
    }
}
