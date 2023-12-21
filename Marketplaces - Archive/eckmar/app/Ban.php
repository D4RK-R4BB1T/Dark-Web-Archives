<?php

namespace App;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;

class Ban extends Model
{
    use Uuids;
    public $incrementing = false;

}
