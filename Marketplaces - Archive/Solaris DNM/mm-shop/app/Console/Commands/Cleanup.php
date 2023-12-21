<?php

namespace App\Console\Commands;

use App;
use App\EmployeesLog;
use App\Events\OrderFinished;
use App\Events\PositionCreated;
use App\MessengerModels\Message;
use App\Order;
use App\Packages\PriceModifier\ReferralPriceModifier;
use App\Packages\Utils\BitcoinUtils;
use App\QiwiExchange;
use App\QiwiExchangeRequest;
use App\QiwiTransaction;
use App\QiwiWallet;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

class Cleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mm2:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean and close old orders, logs and quests';

    /**
     * The config keys involved in cleanup process.
     *
     * @var string[]
     */
    protected $configKeys = [
        'mm2.order_close_time',
        'mm2.order_problem_close_time',
        'mm2.order_reserve_time',
        'mm2.min_keep_stats_months'
    ];

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
        if (!BitcoinUtils::isPaymentsEnabled() || App::isDownForMaintenance()) {
            return;
        }

        foreach ($this->configKeys as $key) {
            if (!($value = config($key)) || $value == 0) {
                throw new Exception('Something wrong with config. key: ' . $key . ', value: ' . $value);
            }
        }

        // finishing not problem orders without review
        $orders = Order::where('status', Order::STATUS_PAID)
            ->where('updated_at', '<=', Carbon::now()->addHours(-config('mm2.order_close_time')))
            ->get();

        foreach ($orders as $order)
        {
            $order->status = Order::STATUS_FINISHED;
            $order->save();
            event(new OrderFinished($order));
        }


        // finishing problem orders without review
        $orders = Order::where('status', Order::STATUS_PROBLEM)
            ->where('updated_at', '<=', Carbon::now()->addHours(-config('mm2.order_problem_close_time')))
            ->get();

        foreach ($orders as $order)
        {
            if ($order->thread) {
                Message::create([
                    'thread_id' => $order->thread->id,
                    'user_id' => -$order->shop_id,
                    'body' => 'Заказ автоматически отмечен как завершенный.',
                    'system' => true
                ]);
            }

            $order->status = Order::STATUS_FINISHED;
            $order->save();
            event(new OrderFinished($order));
        }

        // cleaning not ready pre-orders
        $orders = Order::where('status', Order::STATUS_PREORDER_PAID)->get();

        foreach ($orders as $order)
        {
            if ($order->getPreorderRemainingTime() < 0)
            {
                $qiwiTransaction = QiwiTransaction::whereOrderId($order->id)->first();
                if (!$qiwiTransaction) {
                    $orderPrice = $order->user_price_btc;
                    $order->user->balanceOperation($orderPrice, BitcoinUtils::CURRENCY_BTC, 'Возврат денег за предзаказ');
                }
                $order->delete();
            }
        }

        // cleaning reserved qiwi orders
        $orders = Order::where('status', Order::STATUS_QIWI_RESERVED)
            ->where('created_at', '<=', Carbon::now()->addMinutes(-config('mm2.order_reserve_time')))
            ->get();

        foreach ($orders as $order)
        {
            /** @var QiwiWallet $qiwiWallet */
            $qiwiWallet = $order->qiwiTransaction->qiwiWallet()->lockForUpdate()->first();
            $qiwiWallet->reserved_balance -= $order->qiwiTransaction->amount;
            $qiwiWallet->save();
            if (!$order->package_preorder) {
                $order->position->available = TRUE;
                $order->position->save();
                event(new PositionCreated($order->position));
            }
            $order->qiwiTransaction->delete();
            $order->delete();
        }

        // cleaning old orders
        $orders = Order::where('created_at', '<=', Carbon::now()->addMonths(-config('mm2.min_keep_stats_months')))->get();
        foreach ($orders as $order)
        {
            $order->services()->delete();
            $order->delete();
        }

        // cleaning old employees history
        $employeesLogs = EmployeesLog::where('created_at', '<=', Carbon::now()->addMonths(-config('mm2.min_keep_stats_months')))->get();
        foreach ($employeesLogs as $employeesLog)
        {
            $employeesLog->delete();
        }

        // cleaning reserved exchanges
        $qiwiExchanges = QiwiExchange::all();
        foreach ($qiwiExchanges as $qiwiExchange) {
            /** @var QiwiExchange $qiwiExchange */
            $exchangeRequests = $qiwiExchange->exchangeRequests()->where('status', QiwiExchangeRequest::STATUS_RESERVED)
                ->where('created_at', '<=', Carbon::now()->addMinutes(-$qiwiExchange->reserve_time))
                ->get();

            foreach ($exchangeRequests as $exchangeRequest) {
                $exchangeRequest->error_reason = 'Заявка не была оплачена отмеченной вовремя.';
                $exchangeRequest->status = QiwiExchangeRequest::STATUS_CANCELLED;
                $exchangeRequest->save();
            }
        }
    }
}
