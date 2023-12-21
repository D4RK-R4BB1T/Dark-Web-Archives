<?php

namespace App\Console\Commands;

use App\Packages\CatalogSync\CatalogSynchronization;
use App\Packages\CatalogSync\SynchronizationException;
use App\Packages\QiwiAPI\QiwiAPI;
use Illuminate\Console\Command;

class QiwiApiSync extends Command
{
    use PrependsOutput, PrependsTimestamp;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mm2:qiwi_sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize data with QIWI exchanger';

    /**
     * @var QiwiAPI
     */
    protected $qiwiAPI;

    /**
     * Create a new command instance.
     *
     * @param QiwiAPI $qiwiAPI
     * @internal param Client $client
     */
    public function __construct(QiwiAPI $qiwiAPI)
    {
        $this->qiwiAPI = $qiwiAPI;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle($attempt = 0)
    {
        $attempt++;
        $this->info('Syncing with QIWI handler (attempt #' . $attempt . ')');
        try {
            $this->qiwiAPI->syncWallets();
        } catch (\Exception $e) {
            $this->error('Synchronization is unsuccessful (' . get_class($e) . '): ' . $e->getMessage());
            $this->error($e->getFile() . ' at line ' . $e->getLine());
            if ($attempt < 3) {
                sleep(10);
                return $this->handle($attempt);
            }
        }

        $this->info('Finished synchronization with QIWI handler.');
    }
}
