<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DecryptTelegrams extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (\App\User::all() as $user) {
            if (!empty($user->contacts_telegram)) {
                $user->contacts_telegram = decrypt($user->contacts_telegram);
                $user->save();
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->index('contacts_telegram');
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
