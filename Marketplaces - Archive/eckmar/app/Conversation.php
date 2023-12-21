<?php

namespace App;

use App\Traits\Uuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use Uuids;
    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    /**
     * Create conversation for mass message, Sender is null
     *
     * @param User $receiver
     * @return Conversation
     */
    public static function findOrCreateMassMessageConversation(User $receiver): Conversation
    {
        /**
         * Find conversation with this receiver
         */
        $oldConversation = Conversation::whereNull('sender_id')
                                        -> where('receiver_id', $receiver -> id)
                                        -> first();
        // update 'updated_at' timestamp for ordering mass message conversations
        if ($oldConversation != null){
            $oldConversation->updated_at = Carbon::now();
            $oldConversation->save();
        }

        if($oldConversation == null){
            $oldConversation = new Conversation;
            $oldConversation -> receiver_id = $receiver -> id;
            $oldConversation -> save();
        }

        return $oldConversation;
    }

    /**
     * Find the conversation between this two users or create one
     *
     * @param User $sender
     * @param User $receiver
     * @return User|null
     */
    static public function findWithUsersOrCreate(User $sender, User $receiver) : Conversation
    {
        /**
         * Find conversation with any combinations of sender and receiver users
         */
        $oldConversation = Conversation::where(function($q) use ($sender, $receiver){
                $q -> where('sender_id', $sender -> id);
                $q -> where('receiver_id', $receiver -> id);
            })
            -> orWhere(function ($q) use ($sender, $receiver) {
                $q -> where('sender_id', $receiver -> id);
                $q -> where('receiver_id', $sender -> id);
            }) -> first();

        /**
         * If it is not found make new conversation
         */
        if($oldConversation == null)
        {
            $oldConversation = new Conversation;
            $oldConversation -> sender_id = $sender -> id;
            $oldConversation -> receiver_id = $receiver -> id;
            $oldConversation -> save();
        }

        return $oldConversation;
    }

    /**
     * Relationship with the messages
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages()
    {
        return $this -> hasMany(\App\Message::class, 'conversation_id', 'id');
    }


    /**
     * Relationship with the User, as a sender
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function sender()
    {
        return $this -> belongsTo(\App\User::class, 'sender_id', 'id');
    }

    /**
     * Relationship with User as a Receiver
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function receiver()
    {
        return $this -> belongsTo(\App\User::class,'receiver_id', 'id');
    }

    /**
     * Returns if the conversation is started by market and
     *
     * @return bool
     */
    public function isMassConversation() : bool
    {
        return $this -> sender_id == null;
    }

    /**
     * Returns the not logged user in the conversation
     * if the message from the market returns the stub user with fake username
     *
     * @return User
     */
    public function otherUser() : User
    {
        // if the logged user is receiver return sender
        if(auth() -> check() && auth() -> user()  == $this -> receiver)
            if($this -> sender)
                return $this -> sender;
            // if the sender is null then return stub user
            else
                return User::stub(); // non-persisted user

        // other user is receiver
        return $this -> receiver;


    }

    /**
     * Count all messages in conversation that are not user's and not read
     *
     * @return int
     */
    public function unreadMessages() : int
    {
        return $this -> messages() -> where('sender_id', '!=', auth() -> user() -> id) -> where('read', false) -> count();
    }

    /**
     * Mark unread messages as read in this conversation
     */
    public function markMessagesAsRead()
    {
        $this -> messages() -> where('receiver_id', auth() -> user() -> id) -> where('read', false) -> update(['read' => true]);
    }

    /**
     * Update time of the conversation
     */
    public function updateTime()
    {
        $this -> updated_at = Carbon::now();
        $this -> save();
    }

    /**
     * Return dated string for interval of last message
     *
     * @return string
     */
    public function getUpdatedAgoAttribute()
    {
        // if there are no messages return the time of creation
        if($this->messages()->get()->isEmpty())
            return Carbon::parse($this->updated_at) -> diffForHumans();
        return Carbon::parse($this -> messages() -> orderByDesc('created_at') -> first() -> created_at) -> diffForHumans();

    }
    
}
