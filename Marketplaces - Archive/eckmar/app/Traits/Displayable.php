<?php
 namespace App\Traits;

 use Carbon\Carbon;

 /**
  * Trait Displayable
  *
  * All methods used for displaying User or User's properties
  *
  * @package App\Traits
  */
 trait Displayable {
     /**
      * Return last seen time in X time ago format
      * @return mixed
      */
     public function lastSeenForHumans(){
         if ($this->last_seen == null){
             return 'Never signed in';
         }
         $time = Carbon::createFromTimeString($this -> last_seen);
         return $time -> diffForHumans();
     }


     public function getUserGroup(){
        if ($this->admin !== null){
            return [
                'name' => 'Administrator',
                'badge' => true,
                'color' => 'warning'
            ];
        }
        if ($this->vendor !== null){
            return [
                'name' => 'Vendor',
                'badge' => true,
                'color' => 'info'
            ];
        }
        if ($this->hasPermissions()){
            return [
                'name' => 'Moderator',
                'badge' => true,
                'color' => 'secondary'
            ];
        }
        return [
             'name' => 'User',
             'badge' => false,
             'color' => 'default'
         ];
     }
 }