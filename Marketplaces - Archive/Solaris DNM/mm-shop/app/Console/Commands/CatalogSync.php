<?php

namespace App\Console\Commands;

use App\Packages\CatalogSync\CatalogSynchronization;
use App\Packages\CatalogSync\SynchronizationException;
use App\Providers\DynamicPropertiesProvider;
use App\Shop;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class CatalogSync extends Command
{
    use PrependsOutput, PrependsTimestamp;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mm2:catalog_sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize data with catalog';

    /**
     * @var CatalogSynchronization
     */
    protected $synchronzier;

    /**
     * Create a new command instance.
     *
     * @param CatalogSynchronization $synchornizer
     * @internal param Client $client
     */
    public function __construct(CatalogSynchronization $synchornizer)
    {
        $this->synchronzier = $synchornizer;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle($attempt = 0)
    {
        $shop = Shop::getDefaultShop();
        $propertiesProvider = \App::make(DynamicPropertiesProvider::class);
        $propertiesProvider->register($shop->id);

        if(!is_null($propertiesProvider->getBool(DynamicPropertiesProvider::KEY_INTEGRATION_CATALOG))) {
            $this->warn('Catalog synchronization disabled');
            return 0;
        }

        $attempt++;
        $success = false;
        $this->info('Syncing with catalog (attempt #' . $attempt . '); Sync url: ' . $this->synchronzier->getSyncURL());

        try {
            $success = $this->synchronzier->performSync();
        } catch (SynchronizationException $exception) {
            $this->error('Synchronization is unsuccessful (SyncException): ' . $exception->getMessage());
            if ($attempt < 3) {
                sleep(10);
                return $this->handle($attempt);
            }
        } catch (\Exception $e) {
            $this->error('Synchronization is unsuccessful (' . get_class($e) . '): ' . $e->getMessage());
            $this->error($e->getFile() . ' at line ' . $e->getLine());
            if ($attempt < 3) {
                sleep(10);
                return $this->handle($attempt);
            }
        }

        if ($success) {
            $this->info('Finished synchronization with catalog: auth server is: ' . $this->synchronzier->getHomeURL());
        }

        return 0;
    }
}
