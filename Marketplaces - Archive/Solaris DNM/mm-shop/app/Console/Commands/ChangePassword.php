<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ChangePassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:passwd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets random password by ID or username';

    /**
     * @var User
     */
    private $user;

    /**
     * Password change allowed only for this roles
     *
     * @const array
     */
    const PASSWORD_ROLES = ['admin', 'user', 'shop', 'shop_pending'];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->user = new User();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $obj = $this->ask(' > ID or full username');

        if(is_numeric($obj)) {
            $user = \App\User::whereIn('role', self::PASSWORD_ROLES)
                ->find($obj);
        } else {
            $user = \App\User::where('username', '=', $obj)
                ->whereIn('role', self::PASSWORD_ROLES)
                ->first();
        }

        if(!$user) {
            print(" > user not found or role invalid\n");
            return 1;
        }

        $this->user = $user;
        unset($user);

        $yn = Str::lower($this->ask(" > set random password for user {$this->user->username} (ID: {$this->user->id})? [yne] "));

        switch ($yn) {
            case "y":
                $this->randomPasssword();
                break;
            case "n":
                $this->askPassword();
                break;
            case "e":
            default:
                print(" > exit\n");
                break;
        }

        return 0;
    }

    private function randomPasssword()
    {
        $rawstr = str_random(10);
        $this->user->password = \Hash::make($rawstr);
        $this->user->save();
        print(" > \033[38;2;90;150;250m{$this->user->username}\033[0m new password is \033[38;2;250;50;90m$rawstr\033[0m\n");
    }

    private function askPassword()
    {
        $newpassword = $this->ask(' > submit new password');
        $this->user->password = \Hash::make($newpassword);
        $this->user->save();
        print(" > \033[38;2;90;150;250m{$this->user->username}\033[0m new password set\n");
    }
}
