<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewCities3 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $cities = \App\City::where('priority', '<=', '8000')->pluck('title');
        $newCities = ["Алатырь", "Алушта", "Балахна", "Бахчисарай", "Белая Калитва", "Белоозерский", "Бронницы", "Витебск", "Волхов", "Гвардейск", "Голицыно", "Гомель", "Городец", "Гурьевск", "Донской", "Дрезна", "Жигулевск", "Заволжье", "Зеленоград", "Истра", "Кашира", "Кимовск", "Конаково", "Красная Поляна", "Купавна", "Куровское", "Ликино-Дулево", "Лосино-Петровский", "Могилев", "Можайск", "Монино", "Монино", "Мценск", "Нахабино", "Новозыбков", "Озеры", "Орша", "Переславль-Залесский", "Петушки", "Покров", "Полесск", "Семенов", "Советск", "Сортавала", "Старая Купавна", "Старая Русса", "Суворов", "Углич", "Хотьково", "Шатура", "Электрогорск", "Электроугли"];
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
        $spb = \App\City::whereTitle('Санкт-Петербург')->first();
        $spb->priority = 9900;
        $spb->save();
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
