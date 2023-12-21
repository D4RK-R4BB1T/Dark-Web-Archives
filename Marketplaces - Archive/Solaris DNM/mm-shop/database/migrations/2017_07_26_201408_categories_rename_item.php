<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CategoriesRenameItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $category = \App\Category::findOrFail(15);
        $category->title = '*-NBOMe';
        $category->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $category = \App\Category::findOrFail(15);
        $category->title = 'Нбомы';
        $category->save();
    }
}
