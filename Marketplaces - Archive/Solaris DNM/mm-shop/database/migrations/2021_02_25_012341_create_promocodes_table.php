<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromocodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promocodes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employee_id');
            $table->string('code');
            $table->enum('discount_mode', ['price', 'percent']);
            $table->decimal('percent_amount', 16, 8)->nullable()->default(null);
            $table->decimal('price_amount', 16, 8)->nullable()->default(null);
            $table->enum('price_currency', ['btc','rub','usd'])->nullable()->default(NULL);
            $table->enum('mode', ['single_use', 'until_date']);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable()->default(NULL);
            $table->timestamps();

            $table->unique('code');
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promocodes');
    }
}
