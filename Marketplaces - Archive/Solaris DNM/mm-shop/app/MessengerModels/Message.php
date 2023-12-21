<?php
/**
 * File: Message.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\MessengerModels;

use Cmgmyr\Messenger\Models\Models;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * App\MessengerModels\Message
 *
 * @property integer $id
 * @property integer $thread_id
 * @property integer $user_id
 * @property string $body
 * @property boolean $system
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\MessengerModels\Thread $thread
 * @property-read \App\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MessengerModels\Participant[] $participants
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Message whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Message whereThreadId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Message whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Message whereBody($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Message whereSystem($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Message whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Shop $shop
 * @property int $employee_id
 * @property-read \App\Employee $employee
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Message whereEmployeeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\MessengerModels\Message nPerGroup($group, $n = 10)
 */
class Message extends Eloquent
{
    public function scopeNPerGroup($query, $group, $n = 10)
    {
        // queried table
        $table = ($this->getTable());

        // initialize MySQL variables inline
        $query->from( \DB::raw("(SELECT @rank:=0, @group:=0) as vars, {$table}") );

        // if no columns already selected, let's select *
        if ( ! $query->getQuery()->columns)
        {
            $query->select("{$table}.*");
        }

        // make sure column aliases are unique
        $groupAlias = 'group_'.md5(time());
        $rankAlias  = 'rank_'.md5(time());

        // apply mysql variables
        $query->addSelect(\DB::raw(
            "@rank := IF(@group = {$group}, @rank+1, 1) as {$rankAlias}, @group := {$group} as {$groupAlias}"
        ));

        // make sure first order clause is the group order
        $query->getQuery()->orders = (array) $query->getQuery()->orders;
        array_unshift($query->getQuery()->orders, ['column' => $group, 'direction' => 'asc']);

        // prepare subquery
        $subQuery = $query->toSql();

        // prepare new main base Query\Builder
        $newBase = $this->newQuery()
            ->from(\DB::raw("({$subQuery}) as {$table}"))
            ->mergeBindings($query->getQuery())
            ->where($rankAlias, '<=', $n)
            ->getQuery();

        // replace underlying builder to get rid of previous clauses
        $query->setQuery($newBase);
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'messages';

    /**
     * The relationships that should be touched on save.
     *
     * @var array
     */
    protected $touches = ['thread'];

    /**
     * The attributes that can be set with Mass Assignment.
     *
     * @var array
     */
    protected $fillable = ['thread_id', 'user_id', 'employee_id', 'body', 'system'];

    /**
     * Validation rules.
     *
     * @var array
     */
    protected $rules = [
        'body' => 'required',
    ];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $attributes = [])
    {
        $this->table = Models::table('messages');

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

    public $_shop_id = null;
    public function shop()
    {
        if ($this->_shop_id == null && $this->user_id < 0) {
            $this->_shop_id = -$this->user_id;
        }

        return $this->belongsTo('App\Shop', '_shop_id');
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

    public function author()
    {
        if ($this->user_id > 0) {
            return $this->user;
        } else {
            return $this->shop;
        }
    }

    public function employee()
    {
        return $this->belongsTo('App\Employee', 'employee_id', 'id');
    }

    /**
     * Participants relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function participants()
    {
        return $this->hasMany(Models::classname(Participant::class), 'thread_id', 'thread_id');
    }

    /**
     * Recipients of this message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recipients()
    {
        return $this->participants()->where('user_id', '!=', $this->user_id);
    }
}
