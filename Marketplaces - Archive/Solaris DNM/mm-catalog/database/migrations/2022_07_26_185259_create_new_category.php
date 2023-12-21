<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewCategory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $min = DB::table('categories')->select(DB::raw('min(priority) as priority'))->first();

        $category = (new \App\Category);
        $category->title = "Обнал";
        $category->priority = $min->priority - 10;
        $category->save();

        $subcategory = (new \App\Category);
        $subcategory->parent_id = $category->id;
        $subcategory->title = "Обнал BTC";
        $subcategory->priority = $min->priority - 20;
        $subcategory->save();

        Cache::forget('categories');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('categories')->whereIn('title', ['Обнал', 'Обнал BTC'])->delete();
        Cache::forget('categories');
    }
}
