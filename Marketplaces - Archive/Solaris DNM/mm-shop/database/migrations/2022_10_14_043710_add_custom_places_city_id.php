<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomPlacesCityId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('custom_places', function (Blueprint $table) {
            $table->boolean('city_id')->nullable()->default(null)->after('good_id')->index();
            $table->index(['good_id', 'city_id']);
            $table->index(['good_id', 'city_id', 'region_id']);
        });

        \App\CustomPlace::where('region_id', '<=', 12)->update(['city_id' => 1]);
        \App\CustomPlace::where('region_id', '>', 12)->where('region_id', '<=', 30)->update(['city_id' => 3]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropIndex(['good_id', 'city_id']);
            $table->dropIndex(['good_id', 'city_id', 'region_id']);
            $table->dropColumn('custom_places');
        });
    }
}
