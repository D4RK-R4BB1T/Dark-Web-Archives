<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReferralUrls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referral_urls', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('slug');
            $table->double('fee');
            $table->timestamps();
            $table->index('user_id');
            $table->index('slug');
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('referral_url', function (Blueprint $table) {
            //
        });
    }
}
