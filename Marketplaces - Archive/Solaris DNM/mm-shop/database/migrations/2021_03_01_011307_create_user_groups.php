<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->enum('mode', ['manual', 'auto']);
            $table->decimal('percent_amount', 16, 8);
            $table->integer('buy_count')->nullable()->default(null);
            $table->timestamps();

            $table->index(['mode', 'buy_count']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('group_id')->after('referral_fee')->nullable()->default(null);
            $table->index('group_id');
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
