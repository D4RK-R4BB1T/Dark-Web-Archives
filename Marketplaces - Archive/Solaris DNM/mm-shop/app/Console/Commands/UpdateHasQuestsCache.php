<?php

namespace App\Console\Commands;

use App\GoodsPackage;
use Illuminate\Console\Command;

class UpdateHasQuestsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mm2:update_has_quests_cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновить значения has_quests/has_ready_quests у всех упаковок';

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
     */
    public function handle()
    {
        $packages = GoodsPackage::get();
        foreach ($packages as $package) {
            $package->has_ready_quests = $package->availablePositions()->count() > 0;
            $package->has_quests = $package->has_ready_quests || $package->preorder;
            $package->save();
        }
    }
}
