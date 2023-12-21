<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopsGateColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('gate_enabled')->default(false)->after('eos_enabled');
            $table->string('gate_lan_ip')->nullable()->default(null)->after('gate_enabled');
            $table->string('gate_lan_port')->nullable()->default(null)->after('gate_lan_ip');
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
            $table->dropColumn(['gate_enabled', 'gate_lan_ip', 'gate_lan_port']);
        });
    }
}
