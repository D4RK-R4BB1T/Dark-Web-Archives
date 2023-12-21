<?php

namespace App;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use Uuids;
    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    protected $fillable = ['description','route_name','route_params'];

    /**
     * Returns user that notifications is sent to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(){
        return $this->belongsTo(\App\User::class);
    }

    /**
     * Check if user read notification
     *
     * @return bool
     */
    public function isRead(): bool {
        return $this->read == 1;
    }

    /**
     * Get route params
     *
     * @return mixed
     */
    public function getRouteParams(){
        return unserialize($this->route_params);
    }

    /**
     * Get route name
     *
     * @return mixed
     */
    public function getRoute(){
        return $this->route_name;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(){
        $this->read = true;
        $this->save();
    }
}
