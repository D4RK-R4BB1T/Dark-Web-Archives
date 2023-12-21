<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewCities2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $cities = \App\City::where('priority', '<=', '8000')->pluck('title');
        $newCities = ["Алмата", "Нур-Султан", "Апрелевка", "Красное Село", "Горелово", "Пушкин", "Павловск", "Гатчина", "Вырица", "Ломоносов", "Петергоф", "Сосновый бор", "Всеволожск", "Тосно", "Токсово", "Сертолово", "Сестрорецк"];
        $allCities = collect($newCities)->merge($cities)->unique()->sort()->values();
        foreach ($allCities as $i => $title) {
            $priority = 8000 - ($i * 15);
            $cityModel = \App\City::whereTitle($title)->first();
            if ($cityModel) {
                $cityModel->priority = $priority;
                $cityModel->save();
            } else {
                \App\City::create(['title' => $title, 'priority' => $priority]);
            }
        }
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

