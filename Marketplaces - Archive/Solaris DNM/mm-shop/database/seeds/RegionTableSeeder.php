<?php

use Illuminate\Database\Seeder;

class RegionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $moscowRegions = ['ЦАО', 'ВАО', 'САО','СВАО','ЮВАО','ЮАО','ЮЗАО','ЗАО','СЗАО','НАО','ТАО', 'Зеленоград'];
        $moscow = \App\City::find(1);
        $priority = 1000;

        foreach ($moscowRegions as $region) {
            \App\Region::create([
                'city_id' => $moscow->id,
                'parent_id' => null,
                'title' => $region,
                'priority' => $priority
            ]);

            $priority -= 10;
        }

        $spbRegions = ['Адмиралтейский', 'Василеостровский', 'Выборгский', 'Калининский', 'Кировский', 'Колпинский', 'Красногвардейский', 'Красносельский', 'Кронштадский','Курортный', 'Московский','Невский','Петроградский','Петродворцовый','Приморский','Пушкинский','Фрунзенский', 'Центральный'];
        $spb = \App\City::find(3);
        $priority = 1000;

        foreach ($spbRegions as $region) {
            \App\Region::create([
                'city_id' => $spb->id,
                'parent_id' => null,
                'title' => $region . ' район',
                'priority' => $priority
            ]);

            $priority -= 10;
        }
    }
}
