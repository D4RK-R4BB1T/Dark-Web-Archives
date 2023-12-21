<?php

namespace App\Console\Commands;

use App\Jobs\CreateBitcoinWallet;
use App\Wallet;
use Illuminate\Console\Command;

class CreateWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mm2:create_wallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создает пользователю кошелек и активируает аккаунт.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $obj = $this->ask('[~] ID or full username: ');
        $user = \App\User::with(['wallets']);

        if(is_numeric($obj)) {
            $user = $user->find($obj);
        } else {
            $user = $user->where('username', '=', $obj)->first();
        }

        if(!$user) {
            print("[-] user not found!\n");
            return 1;
        }

        if($user->wallets->count() === 0) {
            echo("User $user->username has no wallets, creating task...\n");
            dispatch(new CreateBitcoinWallet($user, Wallet::TYPE_PRIMARY, ['title' => 'Основной кошелек пользователя']));
        } else {
            $ask = $this->ask("User $user->username already has wallet(s), create new [yn]? ");

            if(strtolower($ask) === 'y') {
                echo("Creating task for user $user->username...\n");
                dispatch(new CreateBitcoinWallet($user, Wallet::TYPE_PRIMARY, ['title' => 'Основной кошелек пользователя']));
            }
        }

        return 0;
    }
}
