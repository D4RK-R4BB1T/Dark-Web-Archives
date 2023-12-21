<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExchangesMinMax extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qiwi_exchanges', function (Blueprint $table) {
            $table->double('min_amount', 16, 8)->default(100)->after('reserve_time');
            $table->double('max_amount', 16, 8)->default(15000)->after('min_amount');
        });

        Schema::table('qiwi_exchanges_requests', function (Blueprint $table) {
            $table->string('input')->nullable()->default(null)->after('btc_rub_rate');
        });

        Schema::table('qiwi_exchanges_transactions', function (Blueprint $table) {
            $table->boolean('pay_need_input')->default(false)->after('pay_comment');
            $table->string('pay_input_description')->nullable()->default(null)->after('pay_need_input');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qiwi_exchanges', function (Blueprint $table) {
            //
        });
    }
}
