<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\SyncState
 *
 * @property int $id
 * @property string $sync_server
 * @property string $auth_server
 * @property int $last_sync_at
 * @method static \Illuminate\Database\Query\Builder|\App\SyncState whereAuthServer($value)
 * @method static \Illuminate\Database\Query\Builder|\App\SyncState whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\SyncState whereLastSyncAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\SyncState whereSyncServer($value)
 * @mixin \Eloquent
 */
class SyncState extends Model
{
    protected $table = 'catalog_sync';
    protected $primaryKey = 'id';
    protected $fillable = [
        'sync_server', 'auth_server', 'last_sync_at'
    ];

    protected $casts = [
        'last_sync_at' => 'datetime'
    ];

    public $timestamps = false;

    public static function getDefaultSyncState()
    {
        try {
            return SyncState::find(1);
        } catch (\PDOException $e) { // shop is not initialized yet
            return new SyncState();
        }
    }
}
