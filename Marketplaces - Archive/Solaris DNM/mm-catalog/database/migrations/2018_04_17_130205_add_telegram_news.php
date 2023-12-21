<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTelegramNews extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $text = <<<TEXT
<p>
Команда Solaris представляет новое крупное обновление, доступное для всех тарифных планов. <br /> 
Теперь продавать через Telegram станет намного проще! Мы добавили поддержку telegram-ботов с полной интеграцией с Вашим магазином.
</p>
<p>
В бота выгружаются все доступные товары с прилавка, есть возможность покупки товаров за QIWI и Bitcoin, а полная синхронизация аккаунтов между магазином и ботом позволяет вести детальную статистику по продажам. <br />
Управление ботом происходит через магазин, Вам даже не нужно пользоваться Telegram для торговли. Наши боты крайне устойчивы к блокировкам.
</p>
<p>
Стоимость услуги <strong>150$</strong> ежемесячно. <br />
За дополнительной информацией и по вопросам приобретения обращайтесь к администратору Solaris через форум Darkcon или через один из жаберов, который указан ниже: <br />
<strong>solaris@securejabber.me</strong><br />
<strong>z8888@zloy.im</strong><br />
<strong>zzzz8@exploit.im</strong><br />
<strong>solaris_support@xmpp.co</strong>
</p>
TEXT;

        \App\News::create([
            'title' => 'Боты Telegram',
            'text' => $text,
            'author' => 'kitekat'
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
