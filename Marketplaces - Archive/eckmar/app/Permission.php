<?php

namespace App;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use Uuids;

    /**
     * Every permission belongs to one user
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this -> belongsTo(\App\User::class, 'user_id', 'id');
    }


    public function setUser(User $user)
    {
        $this -> user_id = $user -> id;
    }
}
