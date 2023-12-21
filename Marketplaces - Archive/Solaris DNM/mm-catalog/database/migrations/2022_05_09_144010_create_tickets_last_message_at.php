<?php

use App\Models\Tickets\Message;
use App\Models\Tickets\Ticket;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketsLastMessageAt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('last_message_at')->after('updated_at')->index();
        });

        // обновляем время последних сообщений в тикетах
        Ticket::get()->each(function ($t) {
            if($last_message = Message::where('ticket_id', '=', $t->id)->orderBy('created_at', 'DESC')->first()) {
                $t->last_message_at = $last_message->created_at;
            } else {
                $t->last_message_at = \Carbon\Carbon::now();
            }

            $t->save();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('last_message_at');
        });
    }
}
