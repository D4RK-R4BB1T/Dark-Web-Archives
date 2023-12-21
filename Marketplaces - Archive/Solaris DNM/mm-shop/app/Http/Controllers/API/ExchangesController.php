<?php
/**
 * File: ExchangesController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Packages\ExchangeAPI\ExchangeAPI;
use App\Packages\ExchangeAPI\ExchangeAPIException;
use App\QiwiExchange;
use App\Shop;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExchangesController extends Controller
{
    /** @var QiwiExchange */
    protected $qiwiExchange;

    private function error($message)
    {
        return response()->json([
            'status' => 'error',
            'error' => $message
        ]);
    }

    public function handler(ExchangeAPI $exchangeAPI, Request $request)
    {
        $validatorRules = [
            'exchange_id' => 'required|numeric',
            'shop_key' => 'required',
            'action' => 'required',
            'request' => 'present'
        ];

        $validator = \Validator::make($request->all(), $validatorRules);
        if (!$validator->passes()) {
            return $this->error($validator->errors()->first());
        }

        $this->qiwiExchange = Shop::getDefaultShop()->qiwiExchanges()
            ->where('id', $request->get('exchange_id'))
            ->first();

        if (!$this->qiwiExchange || $this->qiwiExchange->api_key !== $request->get('shop_key')) {
            return $this->error('Авторизация не удалась.');
        }

        $exchangeAPI->setQiwiExchange($this->qiwiExchange);
        try {
            switch ($request->get('action')) {
                case 'update_rates':
                    $response = $exchangeAPI->updateRates($request->get('request'));
                    break;

                case 'exchange_result':
                    $response = $exchangeAPI->exchangeResult($request->get('request'));
                    break;

                case 'update_settings':
                    $response = $exchangeAPI->updateSettings($request->get('request'));
                    break;

                case 'get_settings':
                    $response = $exchangeAPI->getSettings($request->get('request'));
                    break;

                default:
                    return $this->error('Неизвестный метод');
            }
        } catch (ValidationException $exception) {
            return $this->error($exception->validator->errors()->first());
        } catch (ExchangeAPIException $e) {
            return $this->error($e->getMessage());
        }

        return response()->json([
            'status' => 'ok',
            'response' => $response
        ]);
    }
}