<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeedbackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table -> uuid('id');
            $table -> uuid('vendor_id');
            $table -> uuid('buyer_id') -> nullable();
            $table -> uuid('product_id') -> nullable(); // product is null when it is deleted
            $table -> text('product_name');
            $table ->decimal('product_value', 16, 2);
            $table -> enum('type',['positive','neutral','negative']);
            $table -> tinyInteger('quality_rate');
            $table -> tinyInteger('communication_rate');
            $table -> tinyInteger('shipping_rate');
            $table -> text('comment');
            $table->timestamps();

            $table -> primary('id');
            $table -> foreign('vendor_id') -> references('id') -> on('vendors') -> onDelete('cascade');
            $table -> foreign('product_id') -> references('id') -> on('products') -> onDelete('set null');
            $table -> foreign('buyer_id') -> references('id') -> on('users') -> onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('feedback');
    }
}
