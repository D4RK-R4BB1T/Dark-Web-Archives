<?php
/**
 * File: QiwiAPI.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Packages\QiwiAPI;


use App\Employee;
use App\QiwiTransaction;
use App\QiwiWallet;
use App\Shop;
use Carbon\Carbon;
use GuzzleHttp\Client;

class QiwiAPI
{
    /** @var Shop */
    protected $shop;

    /** @var Client */
    protected $client;

    public function __construct(Client $client)
    {
        $this->shop = Shop::getDefaultShop();
        $this->client = $client;
    }

    private function _call($method, $request = [])
    {
        $response = $this->client->post($this->shop->integrations_qiwi_api_url . '?action=' . $method, [
            'json' => [
                'shop_key' => $this->shop->integrations_qiwi_api_key,
                'action' => $method,
                'request' => $request
            ]
        ]);

        return $response->getBody();
    }

    private function _handleResponse($body)
    {
        $response = json_decode($body, true);
        if (empty($response)) {
            $this->_setLastResponse($body, 'Failed to decode JSON');
            return null;
        }

        $validatorRules = [
            'status' => 'required|in:ok,error',
            'error' => 'required_if:status,==,error'
        ];

        $validator = \Validator::make($response, $validatorRules);

        if (!$validator->passes()) {
            $this->_setLastResponse($body, 'Failed: ' . $validator->errors()->first());
            return null;
        }

        if ($response['status'] === 'error') {
            $this->_setLastResponse($body, 'Failed, got an error: ' . $response['error']);
            return null;
        }

        return $response['response'] ?? [];
    }

    private function _setLastResponse($response, $comment = false)
    {
        $response = preg_replace('/"password"\:"(.*?)"/i', '"password":"### hidden ###"', $response);

        if (!$this->shop || !$this->shop->isQiwiApiEnabled()) {
            return null;
        }

        if ($comment) {
            $response .= PHP_EOL . '-------' . PHP_EOL . $comment;
        }

        $this->shop->integrations_qiwi_api_last_response = $response;
        $this->shop->integrations_qiwi_api_last_sync_at = Carbon::now();
        $this->shop->save();
    }

    public function listWallets()
    {
        if (!$this->shop || !$this->shop->isQiwiApiEnabled()) {
            return null;
        }

        $result = null;
        try {
            $result = $this->_call('list_wallets');
        } catch (\Exception $e) {
            $this->_setLastResponse('Connection error', $e->getMessage());
            throw $e;
        }

        $wallets = $this->_handleResponse($result);
        if (!$wallets) {
            return null;
        }

        $validatorRules = [
            '*.login' => 'required|numeric|min:0|digits_between:11,12',
            '*.password' => 'required',
            '*.daily_limit' => 'required|numeric|min:0',
            '*.monthly_limit' => 'required|numeric|min:0'
        ];

        $validator = \Validator::make($wallets, $validatorRules);
        if (!$validator->passes()) {
            $this->_setLastResponse($result, 'Failed: ' . $validator->errors()->first());
            return null;
        }

        return [$result, $wallets];
    }

    public function syncWallets()
    {
        list ($response, $wallets) = $this->listWallets();
        if (!$wallets) {
            return true;
        }

        $maxWalletsCount = $this->shop->getTotalAvailableQiwiWalletsCount();

        $receivedWalletsCount = 0;
        $truncatedWalletsCount = 0;

        $availableWalletIds = [];
        foreach ($wallets as $i => $wallet)
        {
            if ($i > $maxWalletsCount - 1) {
                $truncatedWalletsCount++;
                continue;
            }

            /** @var QiwiWallet $qiwiWallet */
            $qiwiWallet = $this->shop->qiwiWallets()->where('login', $wallet['login'])->first();
            if ($qiwiWallet === null) {
                $qiwiWallet = new QiwiWallet([
                    'shop_id' => $this->shop->id,
                    'login' => $wallet['login'],
                    'password' => $wallet['password'],
                    'daily_limit' => $wallet['daily_limit'],
                    'monthly_limit' => $wallet['monthly_limit']
                ]);
            }
            $qiwiWallet->daily_limit = $wallet['daily_limit'];
            $qiwiWallet->monthly_limit = $wallet['monthly_limit'];
            if ($qiwiWallet->password !== $wallet['password']) {
                $qiwiWallet->password = $wallet['password'];
                $qiwiWallet->status = QiwiWallet::STATUS_ACTIVE;
                $qiwiWallet->last_checked_at = NULL;
            }
            $qiwiWallet->save();
            $availableWalletIds[] = $qiwiWallet->id;
            $receivedWalletsCount++;
        }

        QiwiWallet::whereNotIn('id', $availableWalletIds)->delete();

        $comment = 'Success! ' . $receivedWalletsCount . ' wallet(s) received.';
        if ($truncatedWalletsCount > 0) {
            $comment .= "\n";
            $comment .= 'Warning! Last ' . $truncatedWalletsCount . ' wallet(s) was skipped because amount of received wallets exceeds maximum wallets count.';
        }

        $this->_setLastResponse($response, $comment);
        return true;
    }
}