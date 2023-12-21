<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class QiwiExchangesLastResponse extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qiwi_exchanges', function (Blueprint $table) {
            $table->mediumText('last_response')->nullable()->default(null)->after('active');
            $table->timestamp('last_response_at')->nullable()->default(null)->after('last_response');
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
