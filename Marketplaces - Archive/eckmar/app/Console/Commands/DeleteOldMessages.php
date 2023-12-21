<?php

namespace App\Console\Commands;

use App\Message;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteOldMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messages:delete {days?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old messages.';

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
        // load default from config if there is no option
        $daysOldMessages = $this->argument('days') != null ? intval($this->argument('days')) : config('marketplace.days_old_messages');
        $this->info($daysOldMessages);
        return;


        $numberOfMessages = Message::where('created_at', '<' ,Carbon::now()->subDays($daysOldMessages))->delete();
        if($numberOfMessages>0){
            $this->info("Deleted $numberOfMessages messages");
        }
        else {
            $this -> warn("There are no messages older than $numberOfMessages days!");
        }

    }
}
