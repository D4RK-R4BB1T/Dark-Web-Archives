<?php
/**
 * File: CatalogSynchronization.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Packages\CatalogSync;


use App\Good;
use App\GoodsPackage;
use App\Order;
use App\Packages\Utils\BitcoinUtils;
use App\Shop;
use App\SyncState;
use App\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Encryption\Encrypter;
use Nbobtc\Command\Command;

class CatalogSynchronization
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var SyncState
     */
    protected $syncState;

    /**
     * @var Shop
     */
    protected $shop;

    /**
     * @var Encrypter
     */
    protected $encrypter;

    /**
     * @var BitcoinUtils
     */
    protected $bitcoinUtils;
    private $bitcoinBlockCount = null;
    private $bitcoinConnections = null;

    public function __construct(Client $client, Encrypter $encrypter)
    {
        $this->client = $client;
        $this->encrypter = $encrypter;

        $this->shop = Shop::getDefaultShop();
        $this->syncState = SyncState::getDefaultSyncState();
        $this->bitcoinUtils = resolve('App\Packages\Utils\BitcoinUtils');
    }

    protected function getShopState()
    {
        try {
            if (is_null($this->bitcoinConnections)) {
                $bitcoinInfo = $this->bitcoinUtils->sendCommand(new Command('getnetworkinfo'));
                $this->bitcoinConnections = !empty($bitcoinInfo->result)
                    ? $bitcoinInfo->result->connections
                    : -1;
            }

            if (is_null($this->bitcoinBlockCount)) {
                $bitcoinInfo = $this->bitcoinUtils->sendCommand(new Command('getblockcount'));
                $this->bitcoinBlockCount = !empty($bitcoinInfo->result)
                    ? $bitcoinInfo->result
                    : -1;
            }
        } catch (\Exception $e) {
            $this->bitcoinConnections = -1;
            $this->bitcoinBlockCount = -1;
        }

        $usersCount = User::count();
        return [
            'app_id' => $this->getAppId(),
            'app_key' => catalog_key(),
            'title' => config('mm2.application_title'),
            'url' => url('/shop/' . $this->shop->slug),
            'image_url' => url($this->shop->avatar()),
            'rating' => $this->shop->getRating(),
            'plan' => $this->shop->plan,
            'expires_at' => $this->shop->expires_at->format('U'),
            'users_count' => $usersCount,
            'orders_count' => $this->shop->buy_count,
            'bitcoin_connections' => $this->bitcoinConnections,
            'bitcoin_block_count' => $this->bitcoinBlockCount,
            'local_ip' => config('mm2.local_ip'),
            'app_port' => config('mm2.app_port'),
            'gate_enabled' => config('mm2.gate_enabled'),
        ];
    }

    protected function getGoodsState()
    {
        $goods = $this->shop->availableGoods()
            ->with(['cities', 'availablePackages', 'availablePackages.availablePositions'])
            ->withCount(['orders'])
            ->get();

        $state = [];
        foreach ($goods as $good) {
            /** @var Good $good */
            $item = [
                'id' => $good->id,
                'title' => $good->title,
                'category_id' => $good->category_id,
                'description' => $good->description,
                'image_url' => url($good->image_url),
                // TODO: Оставлено для совместимости, потом удалить
                'has_quests' => $good->whereHas('packages', function ($packages) {
                    $packages->where('has_quests', true);
                })->exists(),
                'has_ready_quests' => $good->whereHas('packages', function ($packages) {
                    $packages->where('has_ready_quests', true);
                })->exists(),
                'buy_count' => $good->buy_count,
                'reviews_count' => $good->reviews()->count(),
                'rating' => $good->getRating(),
                'cities' => $good->cities->pluck('id'),
                'packages' => $good->availablePackages->map(function ($package) {
                    /** @var GoodsPackage $package */
                    $positions = $package->availablePositions->unique(function($position) {
                        $regionId = $position->region ? $position->region->id : 0;
                        $customPlaceId = $position->customPlace ? $position->customPlace->id : 0;
                        return $regionId . '_' . $customPlaceId;
                    });

                    return [
                        'id' => $package->id,
                        'city_id' => $package->city_id,
                        'amount' => $package->amount,
                        'measure' => $package->measure,
                        'price' => $package->price,
                        'currency' => $package->currency,
                        'preorder' => $package->preorder,
                        'has_quests' => $package->has_quests,
                        'has_ready_quests' => $package->has_ready_quests,
                        'positions' => $positions->map(function($position) {
                            if (!$position->subregion_id && !$position->custom_place_id) {
                                return [
                                    'region_id' => NULL,
                                    'custom_place_id' => NULL,
                                    'custom_place_title' => NULL
                                ];
                            }

                            if ($position->region) {
                                return [
                                    'region_id' => $position->subregion_id,
                                    'custom_place_id' => NULL,
                                    'custom_place_title' => NULL
                                ];
                            }

                            if ($position->customPlace) {
                                return [
                                    'region_id' => $position->customPlace->region_id,
                                    'custom_place_id' => $position->custom_place_id,
                                    'custom_place_title' => $position->customPlace->title
                                ];
                            }
                            return NULL;
                        })->filter(function ($i) {
                            return !is_null($i);
                        })->values()
                    ];
                })
            ];

            $state[] = $item;
        }

        return $state;
    }

    protected function getOrdersState()
    {
        $catalogUserIds = User::whereRole(User::ROLE_CATALOG)->pluck('id');
        $orders = Order::whereNotIn('status', [
            Order::STATUS_QIWI_RESERVED,
            Order::STATUS_QIWI_PAID
        ]) // no need to unpaid qiwi orders
        ->whereIn('user_id', $catalogUserIds) // only catalog users
        ->where('updated_at', '>=', $this->syncState->last_sync_at ?: Carbon::createFromTimestampUTC(0)) // only which not was synced before
        ->with(['position', 'review', 'user'])
            ->get();

        $state = [];

        foreach ($orders as $order) {
            /** @var Order $order */
            if ($order->review) {
                $review = [
                    'text' => $order->review->text,
                    'shop_rating' => $order->review->shop_rating,
                    'dropman_rating' => $order->review->dropman_rating,
                    'item_rating' => $order->review->item_rating,
                    'reply_text' => $order->review->reply_text,
                    'created_at' => $order->review->created_at->format('U')
                ];
            } else {
                $review = null;
            }

            if ($order->position) {
                $position = [
                    'quest' => $order->position->quest
                ];
            } else {
                $position = null;
            }

            $item = [
                'id' => $order->id,
                'city_id' => $order->city_id,
                'good_id' => $order->good_id,
                'good_title' => $order->good_title,
                'good_image_url' => url($order->good_image_url),
                'package_amount' => $order->package_amount,
                'package_measure' => $order->package_measure,
                'package_price' => $order->package_price,
                'package_currency' => $order->package_currency,
                'package_preorder' => $order->package_preorder,
                'package_preorder_time' => $order->package_preorder_time,
                'status' => $order->status,
                'comment' => $order->comment,
                'created_at' => $order->created_at->format('U'),
                'updated_at' => $order->updated_at->format('U'),
                'review' => $review,
                'position' => $position,
                'user' => ltrim($order->user->username, '@')
            ];

            $state[] = $item;
        }

        return $state;
    }

    public function getSyncURL()
    {
        if(!config('mm2.local_sync_url')) {
            return 'http://' . $this->syncState->sync_server . '/api/sync';
        }

        return 'http://' . config('mm2.local_sync_url') . '/api/sync';
    }

    public function getAuthURL()
    {
        return 'http://' . $this->syncState->auth_server . '/auth/transparent';
    }

    public function getHomeURL()
    {
        return 'http://' . $this->syncState->auth_server;
    }

    public function getAppId()
    {
        return config('mm2.application_id');
    }

    public function performSync()
    {
        if (!$this->shop || !$this->syncState || !$this->shop->enabled) {
            return false; // shop is not initialized yet
        }

        \URL::forceRootUrl(config('mm2.application_onion_url'));

        $lastSyncStart = Carbon::now();

        $request = [
            'shop' => $this->getShopState(),
            'goods' => $this->getGoodsState(),
            'orders' => $this->getOrdersState()
        ];

        if(config('app.debug')) {
            if(!is_dir(storage_path() . '/logs/dumps')) {
                mkdir(storage_path() . '/logs/dumps', 0770, true);
            }

            file_put_contents(storage_path() . '/logs/dumps/export_'.microtime(true).'.json', json_encode($request, JSON_PRETTY_PRINT));
        }

        $json = json_encode($request);
        $gzipedJSON = gzencode($json);
        $encryptedJson = $this->encryptData($gzipedJSON);

        try {
            $response = $this->client->post($this->getSyncURL(), [
                'form_params' => [
                    'data' => $encryptedJson
                ]
            ]);
        } catch (\Exception $e) {
            throw new SynchronizationException('Could not perform request: ' . $e->getMessage());
        }

        $responseJSON = json_decode($response->getBody());
        if (!$responseJSON || !property_exists($responseJSON, 'response')) {
            throw new SynchronizationException('Could not decode response JSON: response is: ' . $response->getBody());
        }

        $this->syncState->last_sync_at = $lastSyncStart;
        $this->syncState->sync_server = $responseJSON->response->sync_server;
        $this->syncState->auth_server = $responseJSON->response->auth_server;
        $this->syncState->save();
        return true;
    }

    /**
     * @param $data
     * @param bool $serialize
     * @return string
     */
    public function encryptData($data, $serialize = true)
    {
        return $this->encrypter->encrypt($data, $serialize);
    }

    /**
     * @param $data
     * @param bool $unserialize
     * @return string
     */
    public function decryptData($data, $unserialize = true)
    {
        return $this->encrypter->decrypt($data, $unserialize);
    }

    public function transparentAuthURL($username, $password, $back)
    {
        $loginData = $this->encryptData([
            'app_id' => $this->getAppId(),
            'app_key' => catalog_key(),
            'username' => $username,
            'password' => $password,
            'back' => $back
        ]);

        return $this->getAuthURL() . '?token=' . $loginData;
    }
}