<?php

namespace App;

use App\Exceptions\RequestException;
use App\Traits\Uuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use Uuids;

    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    /**
     * Opens new ticket with the title, of logged user
     *
     * @param string $title
     * @return Ticket
     * @throws RequestException
     */
    public static function openTicket(string $title) : Ticket
    {
        if(!auth() -> check())
            throw new RequestException("There is no logged user!");

        $newTicket = new Ticket;
        $newTicket -> title = $title;
        $newTicket -> answered = false;
        $newTicket -> user_id = auth() -> user() -> id;
        $newTicket -> save();

        return $newTicket;
    }


    /**
     * Relationship of the user who made a Ticket
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this -> belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with the Ticket replies
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function replies()
    {
        return $this -> hasMany(TicketReply::class, 'ticket_id', 'id');
    }


    /**
     * Time passed
     *
     * @return string
     */
    public function getTimePassedAttribute()
    {
        return Carbon::parse($this -> created_at) -> diffForHumans();
    }
}
