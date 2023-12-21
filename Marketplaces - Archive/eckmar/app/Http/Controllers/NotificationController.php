<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Now many notifications to display per page
     *
     * @var int
     */
    private $notificationsPerPage = 35;

    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * View notifications of a user
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function viewNotifications(){
        $notifications = auth()->user()->notifications()->orderBy('created_at','desc')->paginate($this->notificationsPerPage);
        foreach ($notifications->where('read',0) as $notification){
            $notification->markAsRead();
        }
        return view('profile.notifications')->with([
            'notifications' => $notifications
        ]);
    }
    
    public function deleteNotifications(){
        $notifications = auth()->user()->notifications()->delete();
        session()->flash('success','Notifications deleted succesfully');
        return redirect()->back();
    }
}
