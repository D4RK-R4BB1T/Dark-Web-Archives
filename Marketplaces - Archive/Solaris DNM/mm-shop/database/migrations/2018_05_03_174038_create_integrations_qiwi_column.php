<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntegrationsQiwiColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('integrations_qiwi_api')->default(false)->after('integrations_telegram_news');
            $table->string('integrations_qiwi_api_url')->nullable()->default(null)->after('integrations_qiwi_api');
            $table->string('integrations_qiwi_api_key')->nullable()->default(null)->after('integrations_qiwi_api_url');
            $table->text('integrations_qiwi_api_last_response')->nullable()->default(null)->after('integrations_qiwi_api_key');
            $table->timestamp('integrations_qiwi_api_last_sync_at')->nullable()->default(null)->after('integrations_qiwi_api_last_response');
        });
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
