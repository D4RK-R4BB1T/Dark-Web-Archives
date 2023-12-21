<?php

namespace App;

use App\Traits\Uuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PGPKey extends Model
{
    use Uuids;

    protected $table = "pgpkeys";

    /**
     * Return the owner of the key
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this -> hasOne(\App\User::class, 'id', 'user_id');
    }

    /**
     * Return Carbon object for time formating for created at
     *
     * @return Carbon
     */
    public function timeUntil()
    {
        return new Carbon($this -> created_at);
    }
}
