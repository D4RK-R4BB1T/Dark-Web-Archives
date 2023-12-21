<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class ModifyTicketsCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $values = [
            "'common_seller_question'",
            "'common_buyer_question'",
            "'app_for_opening'",
            "'cooperation'",
            "'security_service'",
        ];

        $this->migrate($values);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $values = [
            "'common_seller_question'",
            "'common_buyer_question'",
            "'app_for_opening'",
            "'cooperation'",
        ];

        // значение security_service можно не сбрасывать,т.к. security_service будет заменен на пустое значение.
        // в lang файле на этот случай предусмотрен ключ 'Category ' => 'Без категории',
        $this->migrate($values);
    }

    private function migrate($values)
    {
        DB::statement("ALTER TABLE `tickets` CHANGE `category` `category` ENUM(".implode(', ', $values).") NOT NULL DEFAULT 'common_seller_question'");
    }
}
