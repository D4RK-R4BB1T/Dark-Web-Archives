<?php

namespace App\Console\Commands;

use App\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:generatekeys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate marketplace keypair that will be stored in storage.';

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
        Message::generateMarketKeypair();

        if(Storage::exists('marketkey.private') && Storage::exists('marketkey.public'))
            $this -> info('Keypair is generated!');
        else
            $this -> warn('Keypair is not generated');
    }
}
