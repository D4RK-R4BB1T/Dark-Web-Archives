<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*
         * common_seller_question   Общие вопросы от продавцов.
         * common_buyer_question    Общие вопросы от покупателей.
         * app_for_opening          Заявки на открытие магазина.
         * cooperation              Сотрудничество.
         */
        $ticket_categories = ["common_seller_question", "common_buyer_question", "app_for_opening", "cooperation"];

        Schema::create('tickets', function (Blueprint $table) use ($ticket_categories) {
            $table->increments('id');
            $table->string('title', 128);
            $table->enum('category', $ticket_categories)->nullable(false)->default($ticket_categories[0])->index();
            $table->integer('user_id')->nullable(false)->default(0)->unsigned()->index();
            $table->boolean('closed')->nullable(false)->default(false)->index();
            $table->timestamps();

            $table->index(['closed', 'category']);
            $table->index(['user_id', 'closed']);
            $table->index(['user_id', 'closed', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tickets');
    }
}
