<?php

namespace App\Jobs;

use App\Marketplace\Bitmessage\Bitmessage;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class BitmessageNotify implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $tries = 2;
    /**
     * Content to send via bitmessage
     *
     * @var string
     */
    private $content;

    /**
     * Message title
     *
     * @var string
     */
    private $title;

    /**
     * Bitmessage address to send content to
     *
     * @var string
     */
    private $address;


    /**
     * Marketplace address
     *
     * @var \Illuminate\Config\Repository|mixed
     */
    private $sender;


    private $bitmessage;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $title,string $content,string $address)
    {
        $this->content = $content;
        $this->address = $address;
        $this->title = $title;
        $this->sender = config('bitmessage.marketplace_address');
        $this->bitmessage = new Bitmessage(
            config('bitmessage.connection.username'),
            config('bitmessage.connection.password'),
            config('bitmessage.connection.host'),
            config('bitmessage.connection.port')
        );
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->attempts() <= $this->tries){
            $this->bitmessage->sendMessage($this->address,$this->sender,$this->title,$this->content);
        } else {
            $this->delete();
        }

    }
}
