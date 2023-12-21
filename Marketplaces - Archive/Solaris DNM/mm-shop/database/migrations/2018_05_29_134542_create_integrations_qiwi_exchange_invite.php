<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntegrationsQiwiExchangeInvite extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
        //    $table->string('integrations_qiwi_exchange_invite')->nullable()->default(null)->after('integrations_qiwi_api_last_sync_at');
        //    $table->integer('integrations_qiwi_exchange_id')->nullable()->default(null)->after('integrations_qiwi_exchange_invite');
        });

        $shop = \App\Shop::getDefaultShop();
        //$shop->integrations_qiwi_exchange_invite = \Illuminate\Support\Str::random();
        //$shop->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shops', function (Blueprint $table) {
            //
        });
    }
}
