<?php
/**
 * File: Participant.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\MessengerModels;

use Cmgmyr\Messenger\Models\Models;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\MessengerModels\Participant
 *
 * @property integer $id
 * @property integer $thread_id
 * @property integer $user_id
 * @property \Carbon\Carbon $last_read
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property-read \App\MessengerModels\Thread $thread
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Participant whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Participant whereThreadId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Participant whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Participant whereLastRead($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Participant whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Participant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Participant whereDeletedAt($value)
 * @mixin \Eloquent
 */
class Participant extends Eloquent
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'participants';

    /**
     * The attributes that can be set with Mass Assignment.
     *
     * @var array
     */
    protected $fillable = ['thread_id', 'user_id', 'last_read'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'last_read'];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $attributes = [])
    {
        $this->table = Models::table('participants');

        parent::__construct($attributes);
    }

    /**
     * Thread relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function thread()
    {
        return $this->belongsTo(Models::classname(Thread::class), 'thread_id', 'id');
    }

    /**
     * User relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(Models::user(), 'user_id');
    }
}
