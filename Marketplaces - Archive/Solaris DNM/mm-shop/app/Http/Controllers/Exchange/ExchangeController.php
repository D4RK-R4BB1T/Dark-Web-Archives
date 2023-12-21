<?php
/**
 * File: ExchangesController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers\Exchange;


use App\Http\Controllers\Controller;
use App\Jobs\CreateExchange;
use App\Jobs\NotifyExchangePaid;
use App\Packages\Utils\BitcoinUtils;
use App\QiwiExchange;
use App\QiwiExchangeRequest;
use App\Shop;
use Illuminate\Http\Request;

class ExchangeController extends Controller
{
    protected $shop;

    public function __construct()
    {
        parent::__construct();

        $this->shop = Shop::getDefaultShop();
        \View::share('page', 'exchange');
    }

    public function showRegisterForm()
    {
        return view('exchange.register');
    }

    public function register(Request $request)
    {
        $this->validate($request, [
            'invite' => 'required|in:' . $this->shop->integrations_qiwi_exchange_invite,
            'title' => 'required',
            'description' => 'required',
            'api_url' => 'required|regex:/^(https?):\/\/([A-Z\d\.-]{2,})\.([A-Z]{2,})(:\d{2,4})?.*?$/i',
            'api_key' => 'required'
        ]);

        if (\Auth::user()->qiwiExchange) {
            return redirect('/exchange/register')->with('flash_warning', 'Вы уже зарегистрированы в качестве обменника.');
        }

        QiwiExchange::create([
            'shop_id' => $this->shop->id,
            'user_id' => \Auth::user()->id,
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'api_url' => $request->get('api_url'),
            'api_key' => $request->get('api_key')
        ]);

        return redirect('/exchange/management')->with('flash_success', 'Вы успешно зарегистрированы в качестве обменника. Для дальнейшей работы администратор магазина должен выбрать вас в качестве активного обменника.');
    }
    
    public function index()
    {
        \View::share('section', 'exchange');

        $exchange = $this->shop->getActiveQiwiExchange();
        if (!$exchange || !$exchange->active) {
            $exchange = null;
        }

        return view('exchange.index', [
            'exchange' => $exchange
        ]);
    }

    public function exchange(Request $request)
    {
        $exchange = $this->shop->getActiveQiwiExchange();
        if (!$exchange || !$exchange->active) {
            return abort(403);
        }

        $activeExchanges = \Auth::user()->qiwiExchangeRequests()
            ->where('qiwi_exchange_id', $exchange->id)
            ->whereNotIn('status', [QiwiExchangeRequest::STATUS_CANCELLED, QiwiExchangeRequest::STATUS_FINISHED]);

        if ($activeExchanges->count() > 0) {
            return redirect('/exchange')->with('flash_warning', 'У вас есть незавершённый обмен. Дождитесь его завершения перед созданием нового.');
        }

        $btcBalance = $exchange->exchangeWallet()->getRealBalance() * (1 - config('mm2.exchange_api_fee'));
        $minExchangeAmountBtc = $exchange->convertRubles($exchange->min_amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC);
        $maxExchangeAmountBtc = $exchange->convertRubles($exchange->max_amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC);

        $minExchangeAmountBtc = min($btcBalance, $minExchangeAmountBtc);
        $maxExchangeAmountBtc = min($btcBalance, $maxExchangeAmountBtc);

        $rubBalance = $exchange->convertRubles($btcBalance, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB);
        $minExchangeAmountRub = min($rubBalance, $exchange->min_amount);
        $maxExchangeAmountRub = min($rubBalance, $exchange->max_amount);

        $this->validate($request, [
            'currency' => 'required|in:rub,btc',
            'amount_btc' => 'required_if:currency,==,btc|numeric|min:' . $minExchangeAmountBtc . '|max:' . $maxExchangeAmountBtc,
            'amount_rub' => 'required_if:currency,==,rub|numeric|min:' . $minExchangeAmountRub . '|max:' . $maxExchangeAmountRub
        ]);

        $btcAmount = $request->get('currency') === 'btc'
            ? $request->get('amount_btc')
            : $exchange->convertRubles($request->get('amount_rub'), BitcoinUtils::CURRENCY_RUB, BitcoinUtils::CURRENCY_BTC);

        $exchangeRequest = QiwiExchangeRequest::create([
            'qiwi_exchange_id' => $exchange->id,
            'user_id' => \Auth::user()->id,
            'btc_amount' => $btcAmount,
            'btc_rub_rate' => $exchange->btc_rub_rate,
            'status' => QiwiExchangeRequest::STATUS_CREATING,
            'test_mode' => false
        ]);

        dispatch(new CreateExchange($exchangeRequest));
        return redirect('/exchange/' . $exchangeRequest->id, 303)->with('flash_success', 'Заявка на обмен успешно создана.');
    }

    public function request(Request $request, $exchangeRequestId)
    {
        $exchangeRequest = \Auth::user()->qiwiExchangeRequests()
            ->with(['qiwiExchangeTransaction'])
            ->findOrFail($exchangeRequestId);

        return view('exchange.request', [
            'exchangeRequest' => $exchangeRequest
        ]);
    }

    public function requestAction(Request $request, $exchangeRequestId)
    {
        /** @var QiwiExchangeRequest $exchangeRequest */
        $exchangeRequest = \Auth::user()->qiwiExchangeRequests()
            ->with(['qiwiExchangeTransaction'])
            ->where('status', QiwiExchangeRequest::STATUS_RESERVED)
            ->findOrFail($exchangeRequestId);

        $this->validate($request, [
            'action' => 'required|in:cancel,paid'
        ]);

        if ($exchangeRequest->qiwiExchangeTransaction->pay_need_input) {
            $this->validate($request, [
                'input' => 'required_if:action,==,paid'
            ]);
        }

        if ($request->get('action') === 'cancel') {
            $exchangeRequest->status = QiwiExchangeRequest::STATUS_CANCELLED;
            $exchangeRequest->save();
            return redirect('/exchange/history', 303)->with('flash_success', 'Завяка отменена.');
        } else {
            if (!$exchangeRequest->test_mode) {
                $exchangeRequest->qiwiExchange->exchangeWallet()->reserveOperation($exchangeRequest->btc_amount);
            }

            $exchangeRequest->input = $request->get('input');
            $exchangeRequest->status = QiwiExchangeRequest::STATUS_PAID_REQUEST;
            $exchangeRequest->save();

            dispatch(new NotifyExchangePaid($exchangeRequest));
            return redirect('/exchange/' . $exchangeRequestId, 303)->with('flash_success', 'Заявка отмечена как оплаченная.');
        }
    }

    public function history()
    {
        \View::share('section', 'history');
        $exchangeRequests = \Auth::user()->qiwiExchangeRequests()
            ->orderBy('created_at', 'desc')
            ->with(['qiwiExchange'])
            ->paginate(20);

        return view('exchange.history', [
            'exchangeRequests' => $exchangeRequests
        ]);
    }
}