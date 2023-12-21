<?php

use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            'Стимуляторы' => [
                'priority' => 10000,
                'children' => [
                    'Кокаин',
                    'Амфетамин',
                    'Метамфетамин',
                    'Разное'
                ]
            ],
            'Марихуана' => [
                'priority' => 9500,
                'children' => [
                    'Шишки',
                    'Гашиш',
                    'План',
                    'Разное'
                ]
            ],
            'Психоделики' => [
                'priority' => 9000,
                'children' => [
                    'ЛСД',
                    'Грибы',
                    'ДО*',
                    'Нбомы',
                    '2C-*',
                    'Мескалин',
                    'Разное'
                ]
            ],
            'Эйфоретики' => [
                'priority' => 8500,
                'children' => [
                    'МДМА',
                    'Таблетки',
                    'Мефедрон',
                    'МДА',
                    'Метилон (bk-MDMA)',
                    'Разное'
                ]
            ],
            'Аптека' => [
                'priority' => 8000,
                'children' => [
                    'Транквилизаторы',
                    'Депрессанты',
                    'Разное'

                ]
            ],
            'Диссоциативы' => [
                'priority' => 7500,
                'children' => [
                    'Кетамин',
                    'Метоксетамин (MXE)',
                    'Разное'
                ]
            ],
            'Опиаты' => [
                'priority' => 7000,
                'children' => [
                    'Героин',
                    'Метадон',
                    'Трамадол',
                    'Фентанил',
                    'Разное'
                ]
            ],
            'Наборы' => [
                'priority' => 6500,
                'children' => [
                    'Для большой компании',
                    'Для двоих',
                    'В космос',
                    'Разное'
                ]
            ]
        ];

        foreach ($categories as $name => $properties)
        {
            $priority = $properties['priority'];
            $parent = \App\Category::create([
                'title' => $name,
                'priority' => $priority
            ]);

            $priority -= 100;

            foreach ($properties['children'] as $child)
            {
                \App\Category::create([
                    'parent_id' => $parent->id,
                    'title' => $child,
                    'priority' => $priority
                ]);

                $priority -= 10;
            }
        }
    }
}
