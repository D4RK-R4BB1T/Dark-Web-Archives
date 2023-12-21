<?php

namespace App\Events\Admin;


use App\Admin;
use App\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;


class UserPermissionsUpdated
{
    use Dispatchable;

    /**
     * Admin performing request
     *
     * @var User
     */
    public $admin;

    /**
     * User request is being performed on
     *
     * @var User
     */
    public $user;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Admin $admin)
    {
        $this->user = $user;
        $this->admin = $admin;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
