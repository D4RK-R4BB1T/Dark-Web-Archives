<?php
/**
 * File: Messageable.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\MessengerModels\Traits;

use Cmgmyr\Messenger\Models\Message;
use Cmgmyr\Messenger\Models\Models;
use Cmgmyr\Messenger\Models\Participant;
use Cmgmyr\Messenger\Models\Thread;

trait Messageable
{
    public function messages()
    {
        return $this->hasMany(Models::classname(Message::class));
    }

    public function participants()
    {
        return $this->hasMany(Models::classname(Participant::class));
    }

    public function threads()
    {
        return $this->belongsToMany(
            Models::classname(Thread::class),
            Models::table('participants'),
            'user_id',
            'thread_id'
        );
    }

    public function newThreadsCount()
    {
        return $this->threadsWithNewMessages()->count();
    }

    public function threadsWithNewMessages()
    {
        return $this->threads()
            ->where(function ($q) {
                $q->whereNull(Models::table('participants') . '.last_read');
                $q->orWhere(Models::table('threads') . '.updated_at', '>', $this->getConnection()->raw($this->getConnection()->getTablePrefix() . Models::table('participants') . '.last_read'));
            })->where(function ($q) {
                $q->whereNull(Models::table('participants') . '.deleted_at');
            })->get();
    }

}