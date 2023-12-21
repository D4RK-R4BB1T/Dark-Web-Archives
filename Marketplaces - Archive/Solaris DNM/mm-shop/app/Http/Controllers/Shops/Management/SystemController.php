<?php
/**
 * File: SystemController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers\Shops\Management;

use App\Http\Requests\ShopSystemEmployeesPayRequest;
use App\Http\Requests\ShopSystemQiwiPayRequest;
use App\Http\Requests\ShopSystemShopPayRequest;
use App\Income;
use App\Packages\Utils\BitcoinUtils;
use App\Providers\DynamicPropertiesProvider;
use App\Shop;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SystemController extends ManagementController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function($request, $next) {
            $this->authorize('management-sections-system');
            return $next($request);
        });

        \View::share('page', 'system');
    }

    public function index(Request $request)
    {
        return redirect('/shop/management/system/payments');
    }

    public function payments(Request $request)
    {
        \View::share('section', 'payments');
        return view('shop.management.system.payments.index');
    }

    public function showPaymentsShopForm(Request $request)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return redirect('/shop/management/system/payments')->with('flash_warning', 'Прием платежей временно приостановлен, попробуйте позже.');
        }

        $wallets = $this->shop->wallets()->get();
        return view('shop.management.system.payments.shop', [
            'wallets' => $wallets
        ]);
    }

    public function paymentsShopPay(ShopSystemShopPayRequest $request)
    {
        $oldDate = $this->shop->isExpired() ? \Carbon\Carbon::now() : $this->shop->expires_at;
        $newDate = (clone $oldDate)->addMonth();

        if ($this->shop->getTotalPlanPrice() > 0) {
            $description = 'Оплата магазина (' . $oldDate->format('d.m') . ' -> ' . $newDate->format('d.m') . ')';
            $request->wallet->balanceOperation(-$this->shop->getTotalPlanPrice(BitcoinUtils::CURRENCY_RUB), BitcoinUtils::CURRENCY_RUB, $description);
            Income::create([
                'wallet_id' => $request->wallet->id,
                'amount_usd' => $this->shop->getTotalPlanPrice(BitcoinUtils::CURRENCY_USD),
                'amount_btc' => $this->shop->getTotalPlanPrice(BitcoinUtils::CURRENCY_BTC),
                'description' => $description
            ]);
        }

        $this->shop->expires_at = $newDate;
        $this->shop->save();

        return redirect('/shop/management/system/payments')->with('flash_success', 'Настройки сохранены.');
    }

    public function showPaymentsEmployeesForm(Request $request)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return redirect('/shop/management/system/payments')->with('flash_warning', 'Прием платежей временно приостановлен, попробуйте позже.');
        }

        return view('shop.management.system.payments.employees');
    }

    public function showPaymentsEmployeesConfirmForm(Request $request)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return redirect('/shop/management/system/payments')->with('flash_warning', 'Прием платежей временно приостановлен, попробуйте позже.');
        }

        $this->validate($request, [
            'employees_count' => 'required|numeric|min:0|max:100'
        ]);

        $newEmployeesCount = intval($request->get('employees_count'));

        if ($this->shop->getPlanAvailableEmployeesCount() + $newEmployeesCount < $this->shop->employees()->count()) {
            return redirect('/shop/management/system/payments/employees')->with('flash_warning', 'В магазине слишком много активных сотрудников. Увольте сотрудников перед уменьшением дополнительных.');
        }

        $employeesCountDiff =  $newEmployeesCount - $this->shop->employees_count;
        $employeesPrice = null;
        $wallets = null;

        if ($employeesCountDiff == 0) {
            return redirect('/shop/management/system/payments')->with('flash_success', 'Настройки сохранены.');
        }

        if ($employeesCountDiff > 0) { // new employees added
            if ($this->shop->isExpired()) {
                return redirect('/shop/management/system/payments')->with('flash_warning', 'Продлите срок оплаты перед тем, как докупать сотрудников.');
            }

            $wallets = $this->shop->wallets()->get();
            $employeesPrice =
                ($this->shop->getAdditionalEmployeePrice(BitcoinUtils::CURRENCY_USD) / 30) // price for employee per day
                * Carbon::now()->diffInDays($this->shop->expires_at) // days before shop expire
                * $employeesCountDiff; // new employees count
        }

        return view('shop.management.system.payments.employees_confirm', [
            'newEmployeesCount' => $newEmployeesCount,
            'employeesCountDiff' => $employeesCountDiff,
            'employeesPrice' => $employeesPrice,
            'wallets' => $wallets
        ]);
    }

    public function paymentsEmployeesPay(ShopSystemEmployeesPayRequest $request)
    {
        if ($request->employeesCountDiff > 0) {
            if ($this->shop->isExpired()) {
                return redirect('/shop/management/system/payments')->with('flash_warning', 'Продлите срок оплаты перед тем, как докупать сотрудников.');
            }

            $description = 'Оплата сотрудников (' . $this->shop->employees_count . ' -> ' . $request->newEmployeesCount . ')';
            $request->wallet->balanceOperation(-$request->employeesPrice, BitcoinUtils::CURRENCY_USD, $description);
            Income::create([
                'wallet_id' => $request->wallet->id,
                'amount_usd' => $request->employeesPrice,
                'amount_btc' => usd2btc($request->employeesPrice),
                'description' => $description
            ]);
        }

        $this->shop->employees_count = $request->newEmployeesCount;
        $this->shop->save();

        return redirect('/shop/management/system/payments')->with('flash_success', 'Настройки сохранены.');
    }

    public function showIntegrationsForm()
    {
        \View::share('section', 'integrations');

        $qiwiExchanges = $this->shop->qiwiExchanges()->get();

        return view('shop.management.system.integrations.index', [
            'qiwiExchanges' => $qiwiExchanges
        ]);
    }

    public function integrations(Request $request)
    {
        $this->validate($request, [
            'integrations_qiwi_exchange_id' => 'required_with:integrations_qiwi_exchange|in:' . $this->shop->qiwiExchanges()->pluck('id')->implode(',')
        ]);

        $data = [
//            'integrations_eos' => $this->shop->integrations_catalog && $request->has('integrations_eos'),
            'integrations_telegram' => $request->has('integrations_telegram'),
            'integrations_telegram_news' => $request->get('integrations_telegram_news'),
            'integrations_qiwi_exchange_id' => $request->has('integrations_qiwi_exchange') ? $request->get('integrations_qiwi_exchange_id') : null,
            'integrations_quests_map' => $request->has('integrations_quests_map')
        ];

        // не забыть про обр. совм. если будем убирать integrations_catalog из shops
        if(is_null($this->propertiesProvider->getBool(DynamicPropertiesProvider::KEY_INTEGRATION_CATALOG))) {
            $data['integrations_catalog'] = true;
        }

        $this->shop->update($data);

        if ($request->has('integrations_qiwi_exchange')) {
            $qiwiExchange = $this->shop->getActiveQiwiExchange();
            $qiwiExchange->trusted = $request->has('integrations_qiwi_exchange_trusted');
            $qiwiExchange->save();
        }

        return redirect('/shop/management/system/integrations')->with('flash_success', 'Настройки сохранены.');
    }
}