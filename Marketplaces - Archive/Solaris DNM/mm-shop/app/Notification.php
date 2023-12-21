<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Notification
 *
 * @mixin \Eloquent
 * @property int $id
 * @property string $body
 * @property string $actual_until
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Notification whereActualUntil($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Notification whereBody($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Notification whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Notification whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Notification whereUpdatedAt($value)
 */
class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';

    public $fillable = [
        'body', 'actual_until'
    ];
}
