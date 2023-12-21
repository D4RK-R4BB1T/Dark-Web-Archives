<?php
/**
 * File: BalanceController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers;


use App\ExternalExchange;
use App\Jobs\MakePayout;
use App\Packages\Utils\BitcoinUtils;
use Auth;
use Cache;
use Carbon\Carbon;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BalanceController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
        \View::share('page', 'balance');
    }

    public function index(Request $request)
    {
        $operations = \Auth::user()->primaryWallet()
            ->operations()
            ->with(['order'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $exchanges = \Auth::user()->externalExchanges()
            ->where('created_at', '>=', Carbon::now()->addHours(-1))
            ->orderBy('created_at', 'desc')
            ->get();

        return view('balance.index', [
            'operations' => $operations,
            'exchanges' => $exchanges,
            'request' => $request
        ]);
    }

    public function balance(Request $request)
    {
        $this->validate($request, [
            'action' => 'required|in:wallet,payout,exchange,exchange_confirmation'
        ]);

        switch ($request->get('action')) {
            case 'wallet':
                return $this->index($request);
            case 'payout':
                return $this->payout($request);
            case 'exchange':
                return $this->exchange($request);
            case 'exchange_confirmation':
                return $this->exchangeConfirmation($request);
        }

        abort(403);
    }

    public function payout(Request $request)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return redirect('/balance')->with('flash_warning', 'Прием платежей временно приостановлен, попробуйте позже.');
        }

        $this->validate($request, [
            'amount' => 'required|numeric|min:' . config('mm2.bitcoin_min') . '|max:' . min(\Auth::user()->getRealBalance(BitcoinUtils::CURRENCY_BTC) - config('mm2.bitcoin_fee'), config('mm2.bitcoin_max')),
            'wallet' => 'required|between:27,34'
        ]);

        $amount = floatval($request->get('amount'));
        $amount += config('mm2.bitcoin_fee');

        \Auth::user()->balanceOperation(-$amount, BitcoinUtils::CURRENCY_BTC, 'Вывод средств (' . $request->get('wallet') . ')');

        dispatch(new MakePayout(
            $request->get('wallet'), $amount,
            'BalanceController@payout', $request->path(),
            \Auth::user()->primaryWallet()->id, \Auth::user()->id
        ));

        return redirect('/balance', 303)->with('flash_success', 'Транзакция поставлена в очередь на вывод.');
    }

    public function exchange(Request $request)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return redirect('/balance')->with('flash_warning', 'Прием платежей временно приостановлен, попробуйте позже.');
        }

        $cacheKey = Auth::user()->username . '_exchange_attempts';
        if (Cache::has($cacheKey) && Cache::get($cacheKey) >= 2 && !Auth::user()->isBetaUser()) {
            return redirect('/balance')->with('flash_warning', 'Вы недавно создавали заявку, подождите 15 минут и попробуйте еще раз.');
        }

        $this->validate($request, [
            'currency' => 'required|in:' . implode(',', [BitcoinUtils::CURRENCY_RUB, BitcoinUtils::CURRENCY_USD, BitcoinUtils::CURRENCY_BTC]),
            'amount' => 'required|numeric|min:0.000001'
        ]);

        $btcAmount = BitcoinUtils::convert(
            $request->get('amount'),
            $request->get('currency'),
            BitcoinUtils::CURRENCY_BTC
        );
        $operations = \Auth::user()->primaryWallet()
            ->operations()
            ->with(['order'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $exchanges = \Auth::user()->externalExchanges()
            ->where('created_at', '>=', Carbon::now()->addHours(-1))
            ->orderBy('created_at', 'desc')
            ->get();

        return view('balance.index', [
            'operations' => $operations,
            'exchanges' => $exchanges,
            'request' => $request,
            'btcAmount' => $btcAmount,
            'currency' => $request->get('currency'),
            'amount' => $request->get('amount')
        ]);
    }

    public function exchangeConfirmation(Request $request)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return redirect('/balance')->with('flash_warning', 'Прием платежей временно приостановлен, попробуйте позже.');
        }

        $cacheKey = Auth::user()->username . '_exchange_attempts';
        if (Cache::has($cacheKey) && Cache::get($cacheKey) >= 2 && !Auth::user()->isBetaUser()) {
            return redirect('/balance')->with('flash_warning', 'Вы недавно создавали заявку, подождите 15 минут и попробуйте еще раз.');
        }

        $this->validate($request, [
            'currency' => 'required|in:' . implode(',', [BitcoinUtils::CURRENCY_RUB, BitcoinUtils::CURRENCY_USD, BitcoinUtils::CURRENCY_BTC]),
            'amount' => 'required|numeric|min:0.000001'
        ]);

        $btcAmount = BitcoinUtils::convert(
            $request->get('amount'),
            $request->get('currency'),
            BitcoinUtils::CURRENCY_BTC
        );

        $paymentId = Str::random(32);
        $exchangeData = $this->exchangeData($paymentId, $request->get('amount'), $request->get('currency'));
        $exchangeRequestData = json_encode($exchangeData);
        $encrypter = new Encrypter(config('mm2.exchanges_encryption_key'), 'AES-256-CBC');

        ExternalExchange::create([
            'payment_id' => $paymentId,
            'user_id' => Auth::id(),
            'amount' => $btcAmount
        ]);

        $cacheKey = Auth::user()->username . '_exchange_attempts';
        \Cache::add($cacheKey, 0, 15); // 15 minutes
        \Cache::increment($cacheKey);

        return view('balance.exchange_confirmation', [
            'formAction' => config('mm2.exchanges_api_url') . '/api/v1/get_av_exchange',
            'data' => $encrypter->encryptString($exchangeRequestData)
        ]);
    }

    public function redirectToExchange(Request $request, $paymentId)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return redirect('/balance')->with('flash_warning', 'Прием платежей временно приостановлен, попробуйте позже.');
        }

        $exchange = \Auth::user()->externalExchanges()
            ->where('payment_id', $paymentId)
            ->where('created_at', '>=', Carbon::now()->addHours(-1))
            ->firstOrFail();
        $exchangeData = $this->exchangeData($paymentId, $exchange->amount, BitcoinUtils::CURRENCY_BTC);
        $exchangeRequestData = json_encode($exchangeData);
        $encrypter = new Encrypter(config('mm2.exchanges_encryption_key'), 'AES-256-CBC');

        return view('balance.exchange_confirmation', [
            'formAction' => config('mm2.exchanges_api_url') . '/api/v1/get_av_exchange',
            'data' => $encrypter->encryptString($exchangeRequestData)
        ]);
    }

    private function exchangeData($paymentId, $amount, $currency): array
    {
        $btcAmount = BitcoinUtils::convert($amount, $currency, BitcoinUtils::CURRENCY_BTC);
        return [
            'id' => $paymentId,
            'shop_id' => config('mm2.application_id'),
            'btc_address' => Auth::user()->primaryWallet()->segwit_wallet,
            'user_id' => (string) Auth::id(),
            'user_name' => Auth::user()->username,
            'amount' => $btcAmount
        ];
    }
}