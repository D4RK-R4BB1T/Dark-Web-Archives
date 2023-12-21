<?php
/**
 * File: ExchangeAdminController.php
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

class ManagementController extends Controller
{
    /** @var Shop */
    protected $shop;

    /** @var QiwiExchange */
    protected $exchange;

    public function __construct()
    {
        parent::__construct();
        $this->shop = Shop::getDefaultShop();
        $this->middleware(function($request, $next) {
            $this->exchange = \Auth::user()->qiwiExchange;
            if (!$this->exchange) {
                return abort(403);
            }
            \View::share('exchange', $this->exchange);

            return $next($request);
        });

        \View::share('page', 'exchange_management');
        \View::share('shop', $this->shop);
    }

    public function index()
    {
        return redirect('/exchange/management/overview');
    }

    public function overview()
    {
        \View::share('section', 'overview');
        $exchangeRequests = $this->exchange->exchangeRequests()
            ->orderBy('created_at', 'desc')
            ->with(['user'])
            ->paginate(20);

        return view('exchange.management.index', [
            'exchangeRequests' => $exchangeRequests
        ]);
    }

    public function showSettingsForm()
    {
        \View::share('section', 'settings');
        return view('exchange.management.settings');
    }

    public function settings(Request $request)
    {
        $this->validate($request, [
            'description' => 'required',
            'api_url' => 'required|regex:/^(https?):\/\/([A-Z\d\.-]{2,})\.([A-Z]{2,})(:\d{2,4})?.*?$/i',
            'api_key' => 'required',
            'btc_rub_rate' => 'numeric|min:0',
            'min_amount' => 'required|numeric|min:1',
            'max_amount' => 'required|numeric|min:1',
            'reserve_time' => 'required|numeric|min:10'
        ]);

        $this->exchange->update([
            'active' => $request->has('active'),
            'description' => $request->get('description'),
            'api_url' => $request->get('api_url'),
            'api_key' => $request->get('api_key'),
            'btc_rub_rate' => $request->get('btc_rub_rate'),
            'min_amount' => $request->get('min_amount'),
            'max_amount' => $request->get('max_amount'),
            'reserve_time' => $request->get('reserve_time')
        ]);

        return redirect('/exchange/management/settings')->with('flash_success', 'Настройки сохранены.');
    }

    public function settingsInitTest(Request $request)
    {
        if ($request->get('_token') !== csrf_token()) {
            return abort(403);
        }

        if (!$this->exchange->btc_rub_rate) {
            return redirect('/exchange/management/settings')->with('flash_warning', 'Перед тестированием установите курс биткоина к рублю.');
        }

        $exchangeRequest = QiwiExchangeRequest::create([
            'qiwi_exchange_id' => $this->exchange->id,
            'user_id' => \Auth::user()->id,
            'btc_amount' => $this->exchange->convertRubles(100, BitcoinUtils::CURRENCY_RUB, BitcoinUtils::CURRENCY_BTC),
            'btc_rub_rate' => $this->exchange->btc_rub_rate,
            'status' => QiwiExchangeRequest::STATUS_CREATING,
            'test_mode' => TRUE
        ]);

        dispatch(new CreateExchange($exchangeRequest));
        return redirect('/exchange/management/settings')->with('flash_success', 'Тестовый обмен инициирован, ID: ' . $exchangeRequest->id);
    }

    public function settingsTestPaid(Request $request, $exchangeRequestId)
    {
        if ($request->get('_token') !== csrf_token()) {
            return abort(403);
        }

        /** @var QiwiExchangeRequest $exchangeRequest */
        $exchangeRequest = $this->exchange->exchangeRequests()->findOrFail($exchangeRequestId);
        if ($exchangeRequest->status !== QiwiExchangeRequest::STATUS_RESERVED || !$exchangeRequest->test_mode) {
            return abort(403);
        }

        $exchangeRequest->status = QiwiExchangeRequest::STATUS_PAID_REQUEST;
        $exchangeRequest->save();


        dispatch(new NotifyExchangePaid($exchangeRequest));
        return redirect('/exchange/management/settings')->with('flash_success', 'Тестовый обмен отмечен оплаченным, ID: ' . $exchangeRequest->id);
    }

    public function request(Request $request, $exchangeRequestId)
    {
        /** @var QiwiExchangeRequest $exchangeRequest */
        $exchangeRequest = $this->exchange->exchangeRequests()
            ->with(['qiwiExchangeTransaction', 'user'])
            ->findOrFail($exchangeRequestId);

        return view('exchange.management.request', [
            'exchangeRequest' => $exchangeRequest
        ]);
    }

    public function requestFinish(Request $request, $exchangeRequestId)
    {
        if ($request->get('_token') !== csrf_token()) {
            return abort(403);
        }

        /** @var QiwiExchangeRequest $exchangeRequest */
        $exchangeRequest = $this->exchange->exchangeRequests()
            ->with(['qiwiExchangeTransaction', 'user'])
            ->findOrFail($exchangeRequestId);

        if ($exchangeRequest->status !== QiwiExchangeRequest::STATUS_PAID_PROBLEM) {
            return abort(403);
        }

        $exchangeRequest->finish();
        return redirect('/exchange/management/' . $exchangeRequestId)->with('flash_success', 'Биткоины отправлены покупателю.');
    }

    public function requestCancel(Request $request, $exchangeRequestId)
    {
        if ($request->get('_token') !== csrf_token()) {
            return abort(403);
        }

        /** @var QiwiExchangeRequest $exchangeRequest */
        $exchangeRequest = $this->exchange->exchangeRequests()
            ->with(['qiwiExchangeTransaction', 'user'])
            ->findOrFail($exchangeRequestId);

        if ($exchangeRequest->status !== QiwiExchangeRequest::STATUS_PAID_PROBLEM) {
            return abort(403);
        }

        if (!$this->exchange->trusted) {
            return abort(403);
        }

        $exchangeRequest->error_reason = 'Отмена обменником.';
        $exchangeRequest->forceCancel();
        return redirect('/exchange/management/' . $exchangeRequestId)->with('flash_success', 'Заявка отмечена как отменённая.');
    }
}