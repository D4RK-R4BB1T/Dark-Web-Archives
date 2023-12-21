<?php

namespace App\Console\Commands;

use App\Order;
use App\Stat;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mm2:calculate_stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculates stats for admin panel. Should be run once a day at 00:00';

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
        $yesterday = Carbon::yesterday();

        if (!Stat::where('date', $yesterday)->exists()) {
            $visitors = \Cache::get(Stat::getVisitorsCacheKey($yesterday), []);
            $orders_count = Order::where('status', '!=', Order::STATUS_QIWI_RESERVED)
                ->where('created_at', '>=', (clone $yesterday)->startOfDay())
                ->where('created_at', '<=', (clone $yesterday)->endOfDay())
                ->count();

            Stat::create([
                'date' => $yesterday,
                'visitors_count' => count($visitors),
                'visitors_data' => $visitors,
                'orders_count' => $orders_count
            ]);
        }

        Stat::generateGraphs();
    }
}
