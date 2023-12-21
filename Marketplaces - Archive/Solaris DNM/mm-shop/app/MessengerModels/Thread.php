<?php
/**
 * File: Thread.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\MessengerModels;


use App\User;
use Carbon\Carbon;
use Cmgmyr\Messenger\Models\Models;
use Eloquent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\MessengerModels\Thread
 *
 * @property integer $id
 * @property integer $order_id
 * @property string $subject
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MessengerModels\Message[] $messages
 * @property-read mixed $latest_message
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MessengerModels\Participant[] $participants
 * @property-read \App\Order $order
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Thread whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Thread whereOrderId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Thread whereSubject($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Thread whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Thread whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Thread whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Thread forUser($userId)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Thread forUserWithNewMessages($userId)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Thread between($participants)
 * @mixin \Eloquent
 */
class Thread extends Eloquent
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'threads';

    /**
     * The attributes that can be set with Mass Assignment.
     *
     * @var array
     */
    protected $fillable = ['subject', 'order_id', 'system'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $attributes = [])
    {
        $this->table = Models::table('threads');

        parent::__construct($attributes);
    }

    /**
     * Messages relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages()
    {
        return $this->hasMany(Models::classname(Message::class), 'thread_id', 'id');
    }

    public function latestMessage()
    {
        return $this->messages()->orderByDesc('created_at')->orderByDesc('id')->nPerGroup('thread_id', 1);
    }

    /**
     * Returns the latest message from a thread.
     *
     * @return \Cmgmyr\Messenger\Models\Message
     */
    public function getLatestMessage()
    {
        return $this->latestMessage->first();
    }


    /**
     * Participants relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function participants()
    {
        return $this->hasMany(Models::classname(Participant::class), 'thread_id', 'id');
    }

    /**
     * Order relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo('App\Order', 'order_id', 'id');
    }

    /**
     * Returns the user object that created the thread.
     *
     * @return mixed
     */
    public function creator()
    {
        return $this->messages()->oldest()->first()->user;
    }

    /**
     * Returns all of the latest threads by updated_at date.
     *
     * @return mixed
     */
    public static function getAllLatest()
    {
        return self::latest('updated_at');
    }

    /**
     * Returns all threads by subject.
     *
     * @return mixed
     */
    public static function getBySubject($subjectQuery)
    {
        return self::where('subject', 'like', $subjectQuery)->get();
    }

    /**
     * Returns an array of user ids that are associated with the thread.
     *
     * @param null $userId
     *
     * @return array
     */
    public function participantsUserIds($userId = null)
    {
        $users = $this->participants()->select('user_id')->get()->map(function ($participant) {
            return $participant->user_id;
        });

        if ($userId) {
            $users->push($userId);
        }

        return $users->toArray();
    }

    /**
     * Returns threads that the user is associated with.
     *
     * @param $query
     * @param $userId
     *
     * @return mixed
     */
    public function scopeForUser($query, $userId)
    {
        $participantsTable = Models::table('participants');
        $threadsTable = Models::table('threads');

        return $query->join($participantsTable, $this->getQualifiedKeyName(), '=', $participantsTable . '.thread_id')
            ->where($participantsTable . '.user_id', $userId)
            ->where($participantsTable . '.deleted_at', null)
            ->select($threadsTable . '.*');
    }

    /**
     * Returns threads with new messages that the user is associated with.
     *
     * @param $query
     * @param $userId
     *
     * @return mixed
     */
    public function scopeForUserWithNewMessages($query, $userId)
    {
        $participantTable = Models::table('participants');
        $threadsTable = Models::table('threads');

        return $query->join($participantTable, $this->getQualifiedKeyName(), '=', $participantTable . '.thread_id')
            ->where($participantTable . '.user_id', $userId)
            ->whereNull($participantTable . '.deleted_at')
            ->where(function ($query) use ($participantTable, $threadsTable) {
                $query->where($threadsTable . '.updated_at', '>', $this->getConnection()->raw($this->getConnection()->getTablePrefix() . $participantTable . '.last_read'))
                    ->orWhereNull($participantTable . '.last_read');
            })
            ->select($threadsTable . '.*');
    }

    /**
     * Returns threads between given user ids.
     *
     * @param $query
     * @param $participants
     *
     * @return mixed
     */
    public function scopeBetween($query, array $participants)
    {
        return $query->whereHas('participants', function ($q) use ($participants) {
            $q->whereIn('user_id', $participants)
                ->select($this->getConnection()->raw('DISTINCT(thread_id)'))
                ->groupBy('thread_id')
                ->havingRaw('COUNT(thread_id)=' . count($participants));
        });
    }

    /**
     * Add users to thread as participants.
     *
     * @param array|mixed $userId
     */
    public function addParticipant($userId)
    {
        $userIds = is_array($userId) ? $userId : (array) func_get_args();

        collect($userIds)->each(function ($userId) {
            Models::participant()->firstOrCreate([
                'user_id' => $userId,
                'thread_id' => $this->id,
            ]);
        });
    }

    /**
     * Remove participants from thread.
     *
     * @param array|mixed $userId
     */
    public function removeParticipant($userId)
    {
        $userIds = is_array($userId) ? $userId : (array) func_get_args();

        Models::participant()->where('thread_id', $this->id)->whereIn('user_id', $userIds)->delete();
    }

    /**
     * Mark a thread as read for a user.
     *
     * @param int $userId
     */
    public function markAsRead($userId)
    {
        try {
            $participant = $this->getParticipantFromUser($userId);
            $participant->last_read = Carbon::now();
            $participant->save();
        } catch (ModelNotFoundException $e) {
            // do nothing
        }
    }

    /**
     * See if the current thread is unread by the user.
     *
     * @param int $userId
     *
     * @return bool
     */
    public function isUnread($userId)
    {
        try {
            $participant = $this->getParticipantFromUser($userId);

            if ($participant->last_read === null || $this->updated_at->gt($participant->last_read)) {
                return true;
            }
        } catch (ModelNotFoundException $e) {
            // do nothing
        }

        return false;
    }

    /**
     * Finds the participant record from a user id.
     *
     * @param $userId
     *
     * @return mixed
     *
     * @throws ModelNotFoundException
     */
    public function getParticipantFromUser($userId)
    {
        return $this->participants->where('user_id', $userId)->first();
    }

    /**
     * Restores all participants within a thread that has a new message.
     */
    public function activateAllParticipants()
    {
        $participants = $this->participants()->get();
        foreach ($participants as $participant) {
            $participant->restore();
        }
    }

    /**
     * Generates a string of participant information.
     *
     * @param null  $userId
     *
     * @return string
     */
    public function participantsString($userId = null)
    {
        $query = \DB::select('select s.title, s.id as sid, u.role, u.username, u.admin_role_type from participants p
left outer join shops s on p.user_id = -s.id
left outer join users u on p.user_id = u.id
where thread_id = :tid and user_id != :uid and p.deleted_at is NULL',
            ['tid' => $this->id, 'uid' => $userId ?: 0]
        );

        $result = collect($query)->map(function ($row) {
            if ($row->title) {
                return e($row->title);
            } else {
                $user = stub('User', [
                    'role' => $row->role,
                    'username' => $row->username,
                    'admin_role_type' => $row->admin_role_type
                ]);
                return $user->getPublicDecoratedName();
            }
        });

        return $result->implode(', ');
    }

    /**
     * Checks to see if a user is a current participant of the thread.
     *
     * @param $userId
     *
     * @return bool
     */
    public function hasParticipant($userId)
    {
        $participants = $this->participants()->where('user_id', '=', $userId);
        if ($participants->count() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Checks to see if thread is active
     *
     * @param $userId
     *
     * @return bool
     */
    public function hasOtherParticipants($userId)
    {
        $participants = $this->participants()->where('user_id', '!=', $userId);
        if ($participants->count() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Generates a select string used in participantsString().
     *
     * @param $columns
     *
     * @return string
     */
    protected function createSelectString($columns)
    {
        $dbDriver = $this->getConnection()->getDriverName();
        $tablePrefix = $this->getConnection()->getTablePrefix();
        $usersTable = Models::table('users');

        switch ($dbDriver) {
            case 'pgsql':
            case 'sqlite':
                $columnString = implode(" || ' ' || " . $tablePrefix . $usersTable . '.', $columns);
                $selectString = '(' . $tablePrefix . $usersTable . '.' . $columnString . ') as name';
                break;
            case 'sqlsrv':
                $columnString = implode(" + ' ' + " . $tablePrefix . $usersTable . '.', $columns);
                $selectString = '(' . $tablePrefix . $usersTable . '.' . $columnString . ') as name';
                break;
            default:
                $columnString = implode(", ' ', " . $tablePrefix . $usersTable . '.', $columns);
                $selectString = 'concat(' . $tablePrefix . $usersTable . '.' . $columnString . ') as name';
        }

        return $selectString;
    }
    /**
     * Returns array of unread messages in thread for given user.
     *
     * @param $userId
     *
     * @return \Illuminate\Support\Collection
     */
    public function userUnreadMessages($userId)
    {
        $messages = $this->messages()->get();

        try {
            $participant = $this->getParticipantFromUser($userId);
        } catch (ModelNotFoundException $e) {
            return collect();
        }

        if (!$participant->last_read) {
            return $messages;
        }

        return $messages->filter(function ($message) use ($participant) {
            return $message->updated_at->gt($participant->last_read);
        });
    }

    /**
     * Returns count of unread messages in thread for given user.
     *
     * @param $userId
     *
     * @return int
     */
    public function userUnreadMessagesCount($userId)
    {
        return $this->userUnreadMessages($userId)->count();
    }
}
