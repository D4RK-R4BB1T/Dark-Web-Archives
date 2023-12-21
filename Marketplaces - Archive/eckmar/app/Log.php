<?php

namespace App;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use Uuids;
    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $keyType = 'string';


    protected $fillable = [
        'user_id','type','description','performed_on','performed_id'
    ];
    private static $types = [
        'edit',
        'delete',
        'message',
        'dispute/resolve'
    ];

    public function user(){
        return $this->belongsTo('App\User');
    }
    
    public function performedOn(){
        if ($this->performed_on == null) {
            return null;
        }
        $class = $this->performed_on;
        if ($class == 'App\User'){
            $user = $class::find($this->performed_id);
            return [
                'text' => $user -> username,
                'link' => route('admin.users.view',$user->id)
            ];
        }
        return null;
    }

    public static function enter($details){
        $performedOn = $details['performed_on'];
        self::create([
            'user_id' => $details['user_id'],
            'type' => $details['type'],
            'description' => $details['description'],
            'performed_on' => get_class($performedOn),
            'performed_id' => $performedOn->id,
        ]);
    }


}
