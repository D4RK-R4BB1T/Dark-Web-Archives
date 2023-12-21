<?php

use App\Models\Tickets\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class ModifyTicketCategories2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $values = [
            "'".Ticket::CATEGORY_COMMON_SELLER_QUESTION."'",
            "'".Ticket::CATEGORY_COMMON_BUYER_QUESTION."'",
            "'".Ticket::CATEGORY_APPLICATION_FOR_OPENING."'",
            "'".Ticket::CATEGORY_COOPERATION."'",
            "'".Ticket::CATEGORY_SECURITY_SERVICE."'",
            "'".Ticket::CATEGORY_PAYMENT_ERRORS."'"
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
            "'".Ticket::CATEGORY_COMMON_SELLER_QUESTION."'",
            "'".Ticket::CATEGORY_COMMON_BUYER_QUESTION."'",
            "'".Ticket::CATEGORY_APPLICATION_FOR_OPENING."'",
            "'".Ticket::CATEGORY_COOPERATION."'",
            "'".Ticket::CATEGORY_SECURITY_SERVICE."'",
        ];

        $this->migrate($values);
    }

    private function migrate($values)
    {
        DB::statement("ALTER TABLE `tickets` CHANGE `category` `category` ENUM(".implode(', ', $values).") NOT NULL DEFAULT 'common_seller_question'");
    }
}
