<?php

namespace App\Console\Commands;

use App\Events\TransactionConfirmed;
use App\Packages\Loggers\BitcoinLogger;
use App\Packages\Utils\BitcoinUtils;
use App\Transaction;
use App\Wallet;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Nbobtc\Http\Client;

class HandleTransactions extends Command
{
    /** @var Client */
    protected $client;

    /** @var BitcoinLogger */
    protected $log;

    /** @var BitcoinUtils */
    protected $utils;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mm2:handle_transactions {pages?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handles transactions from Bitcoin';

    private $transactionsPerPage;
    private $transactionPages;


    /**
     * Create a new command instance.
     *
     * @param Client $client
     * @param BitcoinLogger $log
     * @param BitcoinUtils $utils
     */
    public function __construct(Client $client, BitcoinLogger $log, BitcoinUtils $utils)
    {
        $this->client = $client;
        $this->log = $log;
        $this->utils = $utils;

        $this->transactionsPerPage = config('mm2.btc.parser.transactions.per_page_count', 500);
        $this->transactionPages = config('mm2.btc.parser.transactions.pages', 0);

        parent::__construct();
    }
    
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            $this->log->info('Payments are disabled, skipping transactions handling.');
            return;
        }

        if($this->hasArgument('pages')) {
            $this->transactionPages = $this->argument('pages');
        }

        for($i=0; $i<=$this->transactionPages; $i++) {
            $this->handleTransactionPage($i);
        }
    }

    private function handleTransactionPage(int $i)
    {
        if($i < 0 || $i > 30) {
            return;
        }

        if($this->transactionPages <= 0) {
            $this->log->debug('Handling transactions.');
        } elseif($i > $this->transactionPages) {
            $this->log->debug('Handling transactions. Page ' . $i . ' / ' . $this->transactionPages);
        }

        $transactions = $this->utils->sendCommand(new \Nbobtc\Command\Command('listtransactions', [config('mm2.application_id'), $this->transactionsPerPage, $i*$this->transactionsPerPage]));

        if (count($transactions->result) == 0) {
            $this->log->warn('It looks like listtransaсtions call was unsuccessful, no transactions will be handled.');
        }

        foreach ($transactions->result as $transaction) {
            // If outcoming transaction
            if ($transaction->category !== Transaction::CATEGORY_RECEIVE) {
                continue;
            }

            if ($transaction->confirmations < 1) {
                continue;
            }

            if (!property_exists($transaction, 'txid')) {
                continue;
            }

            // Check if transaction already exists
            $model = Transaction::whereTxId($transaction->txid)
                ->whereAddress($transaction->address)
                ->whereAmount($transaction->amount)
                /*
                 * TODO это нужно будет включить через некоторое время
                 * ->when(property_exists($transaction, 'vout'), function($query) use ($transaction) {
                    // проверка транзы по vout
                    return $query->where('vout', '=', $transaction->vout);
                })*/
                ->first();

            if (!$model) {
                // withoutGlobalScope(SoftDeletingScope::class) is an alias for withTrashed()
                // but this not works because Laravel is buggy shit and not properly initializes
                // Builder macros when using events from command line tasks
                // see Illuminate\Database\Eloquent\SoftDeletingScope@addWithTrashed
                $wallet = Wallet::withoutGlobalScope(SoftDeletingScope::class)
                    ->where('wallet', $transaction->address)
                    ->orWhere('segwit_wallet', $transaction->address)
                    ->first();

                $model = Transaction::create([
                    'tx_id' => $transaction->txid,
                    'vout' => $transaction->vout,
                    'wallet_id' => ($wallet) ? $wallet->id : NULL,
                    'address' => $transaction->address,
                    'amount' => $transaction->amount,
                    'confirmations' => $transaction->confirmations,
                    'handled' => !$wallet
                ]);
            } elseif(is_null($model->vout) && is_numeric($transaction->vout)) {
                $this->log->info("Transaction #$model->id [$transaction->txid] vout is null. Value updated to $transaction->vout");
                $model->vout = $transaction->vout;
                $model->save();
            } elseif($model->vout === 255) {
                $this->log->warning("Transaction #$model->id [$transaction->txid] vout is 255. Not updating the transaction. Check db and blockchain.");
                continue;
            }

            if ($model->handled) {
                continue;
            }

            $model->confirmations = $transaction->confirmations;
            $model->save();

            if ($model->confirmations >= config('mm2.confirmations_amount')) {
                event(new TransactionConfirmed($model));
            }
        }

        $this->log->debug('Finished transactions handling.');
    }
}
