<?php
/**
 * File: ExchangeAPI.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Packages\ExchangeAPI;


use App\QiwiExchange;
use App\QiwiExchangeRequest;
use App\QiwiExchangeTransaction;
use App\Shop;
use Carbon\Carbon;
use GuzzleHttp\Client;

class ExchangeAPI
{
    /** @var Shop */
    protected $shop;

    /** @var Client */
    protected $client;

    /** @var QiwiExchange */
    protected $exchange;

    public function __construct(Client $client)
    {
        $this->shop = Shop::getDefaultShop();
        $this->client = $client;
    }

    public function setQiwiExchange(QiwiExchange $qiwiExchange)
    {
        $this->exchange = $qiwiExchange;
    }

    private function _call($method, $request = [])
    {
        $response = $this->client->post($this->exchange->api_url . '?action=' . $method, [
            'json' => [
                'shop_key' => $this->exchange->api_key,
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
            throw new ExchangeAPIException('Ошибка на сервере обменника.');
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
            throw new ExchangeAPIException($response['error']);
        }

        return $response['response'] ?? [];
    }

    private function _setLastResponse($response, $comment = false)
    {
        if (!$this->exchange) {
            return null;
        }

        if ($comment) {
            $response .= PHP_EOL . '-------' . PHP_EOL . $comment;
        }

        $this->exchange->last_response = $response;
        $this->exchange->last_response_at = Carbon::now();
        $this->exchange->save();
    }

    public function makeExchange(QiwiExchangeRequest $exchangeRequest)
    {
        if ($exchangeRequest->qiwiExchangeTransaction) {
            return null;
        }

        $result = null;
        try {
            $result = $this->_call('make_exchange', [
                'request_id' => $exchangeRequest->id,
                'btc_amount' => $exchangeRequest->btc_amount,
                'btc_rub_rate' => $exchangeRequest->btc_rub_rate
            ]);
        } catch (\Exception $e) {
            $this->_setLastResponse('Connection error', $e->getMessage());
            throw $e;
        }

        try {
            $response = $this->_handleResponse($result);
            if (!$response) {
                return null;
            }
        } catch (ExchangeAPIException $e) {
            $exchangeRequest->error_reason = $e->getMessage();
            $exchangeRequest->save();
            return null;
        }

        $validatorRules = [
            'address' => 'required',
            'amount' => 'required|numeric|min:0',
            'comment' => 'required',
            'need_input' => 'boolean',
            'input_description' => 'required_if:need_input,==,true'
        ];

        $validator = \Validator::make($response, $validatorRules);
        if (!$validator->passes()) {
            $this->_setLastResponse($result, 'Failed: ' . $validator->errors()->first());
            return null;
        }

        $exchangeTransaction = QiwiExchangeTransaction::create([
            'qiwi_exchange_request_id' => $exchangeRequest->id,
            'pay_amount' => $response['amount'],
            'pay_address' => $response['address'],
            'pay_comment' => $response['comment'],
            'pay_need_input' => $response['need_input'] ?? false,
            'pay_input_description' => $response['input_description'] ?? null
        ]);

        $this->_setLastResponse($result, 'Success! Waiting payment from user.');
        return $exchangeTransaction;
    }

    public function notifyExchange(QiwiExchangeRequest $exchangeRequest)
    {
        $result = null;
        try {
            $request = [
                'request_id' => $exchangeRequest->id,
            ];

            if ($exchangeRequest->input) {
                $request['input'] = $exchangeRequest->input;
            }

            $result = $this->_call('notify_exchange', $request);
        } catch (\Exception $e) {
            $this->_setLastResponse('Connection error', $e->getMessage());
            throw $e;
        }

        try {
            $response = $this->_handleResponse($result);
        } catch (ExchangeAPIException $e) {
            $exchangeRequest->error_reason = $e->getMessage();
            $exchangeRequest->save();
            return null;
        }

        if (is_null($response)) {
            return null;
        }

        $this->_setLastResponse($result, 'Success! Waiting result from exchange.');
        return true;
    }

    public function updateRates($request)
    {
        $validatorRules = [
            'btc_rub' => 'required|numeric|min:0'
        ];

        $validator = \Validator::make($request, $validatorRules);
        $validator->validate();

        $this->exchange->btc_rub_rate = $request['btc_rub'];
        $this->exchange->save();
        return true;
    }

    public function exchangeResult($request)
    {
        $validatorRules = [
            'request_id' => 'required|numeric',
            'success' => 'required|boolean'
        ];

        $validator = \Validator::make($request, $validatorRules);
        $validator->validate();

        /** @var QiwiExchangeRequest $exchangeRequest */
        $exchangeRequest = $this->exchange->exchangeRequests()
            ->where('id', $request['request_id'])
            ->first();

        if (!$exchangeRequest) {
            throw new ExchangeAPIException('Заявка на обмен не найдена.');
        }

        if ($exchangeRequest->status !== QiwiExchangeRequest::STATUS_PAID) {
            throw new ExchangeAPIException('Заявка на обмен не отмечена оплаченной.');
        }

        if ($request['success'] && strtolower($request['success']) !== "false") {
            $exchangeRequest->finish();
        } else {
            $exchangeRequest->status = QiwiExchangeRequest::STATUS_PAID_PROBLEM;
            $exchangeRequest->save();
        }

        return true;
    }

    public function getSettings($request)
    {
        return [
            'active' => $this->exchange->active,
            'min_amount' => $this->exchange->min_amount,
            'max_amount' => $this->exchange->max_amount,
            'reserve_time' => $this->exchange->reserve_time
        ];
    }

    public function updateSettings($request)
    {
        $validatorRules = [
            'active' => 'required|boolean',
            'min_amount' => 'required|numeric|min:1',
            'max_amount' => 'required|numeric|min:1',
            'reserve_time' => 'required|numeric|min:10'
        ];

        $validator = \Validator::make($request, $validatorRules);
        $validator->validate();

        $this->exchange->update([
            'active' => $request['active'],
            'min_amount' => $request['min_amount'],
            'max_amount' => $request['max_amount'],
            'reserve_time' => $request['reserve_time']
        ]);

        return true;
    }
}