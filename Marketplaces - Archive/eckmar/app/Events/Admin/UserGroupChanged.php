<?php

namespace App\Events\Admin;

use App\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserGroupChanged
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
     * User group name
     *
     * @var string
     */
    public $userGroup;

    /**
     * Status
     * true = given
     * false = taken
     *
     * @var bool
     */
    public $status;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user,string $userGroup,bool $status,User $admin)
    {
            $this->admin = $admin;
            $this->user = $user;
            $this->userGroup = $userGroup;
            $this->status = $status;

    }


}
