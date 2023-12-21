<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Category::insert([['parent_id' => 6, 'title' => 'Масло/концентраты', 'priority' => 9375]]);
        \App\Category::insert([
            ['parent_id' => 46, 'title' => 'Склад', 'priority' => 5885],
            ['parent_id' => 46, 'title' => 'Химик / Гроувер', 'priority' => 5884],
            ['parent_id' => 46, 'title' => 'Франшиза', 'priority' => 5860]
        ]);

        $this->addChemicalReactives();
        $this->addDocuments();
        $this->addDigitalGoods();
        $this->addTechnics();
        $this->addCardsAndBanks();
        $this->addGraphicsAndAds();
    }

    public function addChemicalReactives() {
        $category = new \App\Category(['parent_id' => null, 'title' => 'Химические реактивы', 'priority' => 5500]);
        $category->save();

        $categories = [
            ['parent_id' => $category->id, 'title' => 'Конструкторы на мефедрон', 'priority' => 5400],
        ];
        \App\Category::insert($categories);
    }

    public function addDocuments() {
        $category = new \App\Category(['parent_id' => null, 'title' => 'Документы', 'priority' => 5000]);
        $category->save();

        $categories = [
            ['parent_id' => $category->id, 'title' => 'Автодокументы', 'priority' => 4900],
            ['parent_id' => $category->id, 'title' => 'Удостоверения', 'priority' => 4890],
            ['parent_id' => $category->id, 'title' => 'Дипломы', 'priority' => 4880],
            ['parent_id' => $category->id, 'title' => 'Бланки и справки', 'priority' => 4870],
            ['parent_id' => $category->id, 'title' => 'Разное', 'priority' => 4860],
        ];
        \App\Category::insert($categories);
    }

    public function addDigitalGoods() {
        $category = new \App\Category(['parent_id' => null, 'title' => 'Цифровые товары', 'priority' => 4500]);
        $category->save();

        $categories = [
            ['parent_id' => $category->id, 'title' => 'Пробивы', 'priority' => 4400],
            ['parent_id' => $category->id, 'title' => 'Аккаунты', 'priority' => 4390],
            ['parent_id' => $category->id, 'title' => 'VPN / Прокси', 'priority' => 4380],
            ['parent_id' => $category->id, 'title' => 'Разное', 'priority' => 4370],
        ];
        \App\Category::insert($categories);
    }

    public function addTechnics() {
        $category = new \App\Category(['parent_id' => null, 'title' => 'Техника', 'priority' => 4000]);
        $category->save();

        $categories = [
            ['parent_id' => $category->id, 'title' => 'Анонимные ноутбуки', 'priority' => 3900],
            ['parent_id' => $category->id, 'title' => 'Анонимные телефоны', 'priority' => 3890],
            ['parent_id' => $category->id, 'title' => 'Флешки', 'priority' => 3880],
            ['parent_id' => $category->id, 'title' => 'Операционные системы', 'priority' => 3870],
            ['parent_id' => $category->id, 'title' => 'Другое', 'priority' => 3860],
        ];
        \App\Category::insert($categories);
    }

    public function addCardsAndBanks() {
        $category = new \App\Category(['parent_id' => null, 'title' => 'Карты и банки', 'priority' => 3500]);
        $category->save();

        $categories = [
            ['parent_id' => $category->id, 'title' => 'Сим-карты', 'priority' => 3400],
            ['parent_id' => $category->id, 'title' => 'Карты банков', 'priority' => 3390],
            ['parent_id' => $category->id, 'title' => 'Другое', 'priority' => 3380],
        ];
        \App\Category::insert($categories);
    }

    public function addGraphicsAndAds() {
        $category = new \App\Category(['parent_id' => null, 'title' => 'Графика и реклама', 'priority' => 3000]);
        $category->save();

        $categories = [
            ['parent_id' => $category->id, 'title' => 'Дизайн', 'priority' => 2900],
            ['parent_id' => $category->id, 'title' => 'Наружная реклама', 'priority' => 2890],
            ['parent_id' => $category->id, 'title' => 'Другое', 'priority' => 2870],
        ];
        \App\Category::insert($categories);
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
