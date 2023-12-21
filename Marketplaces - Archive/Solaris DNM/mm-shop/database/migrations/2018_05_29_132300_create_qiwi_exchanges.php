<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQiwiExchanges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qiwi_exchanges', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('shop_id');
            $table->integer('user_id');
            $table->string('title');
            $table->text('description');
            $table->string('api_url');
            $table->string('api_key');
            $table->double('btc_rub_rate', 16, 8)->nullable()->default(null);
            $table->boolean('active')->default(false);
            $table->timestamps();

            $table->index(['shop_id']);
            $table->index(['user_id']);
            $table->index(['shop_id', 'user_id']);
        });

        Schema::create('qiwi_exchanges_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('qiwi_exchange_id');
            $table->integer('user_id');
            $table->double('btc_amount', 16, 8);
            $table->double('btc_rub_rate', 16, 8)->nullable()->default(null);
            $table->enum('status', ['creating', 'reserved', 'paid_request', 'paid', 'paid_problem', 'finished', 'cancelled']);
            $table->timestamp('finished_at')->nullable()->default(null);
            $table->timestamps();

            $table->index(['qiwi_exchange_id']);
            $table->index(['user_id']);
            $table->index(['status']);
            $table->index(['qiwi_exchange_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('qiwi_exchanges_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('qiwi_exchange_request_id');
            $table->double('pay_amount', 16, 8);
            $table->string('pay_address');
            $table->string('pay_comment');
            $table->timestamps();

            $table->index(['qiwi_exchange_request_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
