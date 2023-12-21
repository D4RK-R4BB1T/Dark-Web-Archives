<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IntegrationsCatalog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('integrations_catalog')->default(false)->after('integrations_eos');
        });

        Schema::create('catalog_sync', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sync_server');
            $table->string('auth_server');
            $table->timestamp('last_sync_at')->nullable();
        });

        \App\SyncState::create([
            'sync_server' => config('mm2.catalog_default_sync_server'),
            'auth_server' => config('mm2.catalog_default_auth_server'),
            'last_sync_at' => NULL
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('integrations_catalog');
        });

        Schema::dropIfExists('catalog_sync');
    }
}
