<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewCities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\City::create([
            'title' => 'Полевской',
            'priority' => 4625
        ]);

        \App\City::create([
            'title' => 'Ревда',
            'priority' => 4575
        ]);

        \App\City::create([
            'title' => 'Заречный',
            'priority' => 6875
        ]);

        \App\City::create([
            'title' => 'Асбест',
            'priority' => 7875
        ]);

        \App\City::create([
            'title' => 'Каменск-Уральский',
            'priority' => 6475
        ]);

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
