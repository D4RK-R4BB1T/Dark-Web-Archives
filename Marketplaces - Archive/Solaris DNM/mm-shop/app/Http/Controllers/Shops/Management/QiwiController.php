<?php
/**
 * File: QiwiController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers\Shops\Management;


use App\QiwiTransaction;
use App\QiwiWallet;
use Carbon\Carbon;
use Illuminate\Http\Request;

class QiwiController extends ManagementController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function($request, $next) {
            $this->authorize('management-sections-qiwi');
            return $next($request);
        });

        \View::share('page', 'qiwi');
    }

    public function index(Request $request)
    {
        \View::share('section', 'index');
        $qiwiWallets = $this->shop->qiwiWallets()->get();
        return view('shop.management.qiwi.index', [
            'qiwiWallets' => $qiwiWallets
        ]);
    }

    public function showAddForm(Request $request)
    {
        if ($this->shop->isQiwiApiEnabled()) {
            return redirect('/shop/management/qiwi')->with('flash_warning', 'Магазин находится в режиме интеграции с обменником, информация о кошельках не может быть отредактирована.');
        }

        if (($this->shop->getTotalAvailableQiwiWalletsCount() - $this->shop->qiwiWallets()->count()) <= 0) {
            return redirect('/shop/management/qiwi')->with('flash_warning', 'Превышен лимит QIWI-кошельков. Откройте вкладку "Система" для получения большей информации.');
        }

        return view('shop.management.qiwi.add');
    }

    public function add(Request $request)
    {
        if ($this->shop->isQiwiApiEnabled()) {
            return redirect('/shop/management/qiwi')->with('flash_warning', 'Магазин находится в режиме интеграции с обменником, информация о кошельках не может быть отредактирована.');
        }

        if (($this->shop->getTotalAvailableQiwiWalletsCount() - $this->shop->qiwiWallets()->count()) <= 0) {
            return redirect('/shop/management/qiwi')->with('flash_warning', 'Превышен лимит QIWI-кошельков. Откройте вкладку "Система" для получения большей информации.');
        }

        $this->validate($request, [
            'login' => 'required|numeric|min:0|digits:11',
            'password' => 'required|min:6',
            'daily_limit' => 'required|numeric|min:0',
            'monthly_limit' => 'required|numeric|min:0'
        ]);

        QiwiWallet::create([
            'shop_id' => $this->shop->id,
            'login' => $request->get('login'),
            'password' => $request->get('password'),
            'daily_limit' => $request->get('daily_limit'),
            'monthly_limit' => $request->get('monthly_limit'),
        ]);

        return redirect('/shop/management/qiwi')->with('flash_success', 'Кошелек успешно добавлен! Он станет активным после первой проверки баланса.');
    }

    public function showEditForm(Request $request, $walletId)
    {
        if ($this->shop->isQiwiApiEnabled()) {
            return redirect('/shop/management/qiwi')->with('flash_warning', 'Магазин находится в режиме интеграции с обменником, информация о кошельках не может быть отредактирована.');
        }

        /** @var QiwiWallet $qiwiWallet */
        $qiwiWallet = $this->shop->qiwiWallets()->findOrFail($walletId);
        return view('shop.management.qiwi.edit', [
            'qiwiWallet' => $qiwiWallet
        ]);
    }

    public function edit(Request $request, $walletId)
    {
        if ($this->shop->isQiwiApiEnabled()) {
            return redirect('/shop/management/qiwi')->with('flash_warning', 'Магазин находится в режиме интеграции с обменником, информация о кошельках не может быть отредактирована.');
        }

        /** @var QiwiWallet $qiwiWallet */
        $qiwiWallet = $this->shop->qiwiWallets()->findOrFail($walletId);
        $this->validate($request, [
            'login' => 'required|numeric|min:0|digits:11',
            'password' => 'min:6',
            'daily_limit' => 'required|numeric|min:0',
            'monthly_limit' => 'required|numeric|min:0'
        ]);

        if ($qiwiWallet->login !== $request->get('login') || !empty($password = $request->get('password', ''))) {
            $qiwiWallet->last_checked_at = NULL;
            $qiwiWallet->status = QiwiWallet::STATUS_ACTIVE;

            if (!empty($password)) {
                $qiwiWallet->password = $password;
            }
        }
        $qiwiWallet->login = $request->get('login');
        $qiwiWallet->daily_limit = $request->get('daily_limit');
        $qiwiWallet->monthly_limit = $request->get('monthly_limit');
        $qiwiWallet->save();

        return redirect('/shop/management/qiwi')->with('flash_success', 'Кошелек успешно отредактирован.');
    }

    public function showDeleteForm(Request $request, $walletId)
    {
        if ($this->shop->isQiwiApiEnabled()) {
            return redirect('/shop/management/qiwi')->with('flash_warning', 'Магазин находится в режиме интеграции с обменником, информация о кошельках не может быть отредактирована.');
        }

        /** @var QiwiWallet $qiwiWallet */
        $qiwiWallet = $this->shop->qiwiWallets()->findOrFail($walletId);

        return view('shop.management.qiwi.delete');
    }

    public function delete(Request $request, $walletId)
    {
        if ($this->shop->isQiwiApiEnabled()) {
            return redirect('/shop/management/qiwi')->with('flash_warning', 'Магазин находится в режиме интеграции с обменником, информация о кошельках не может быть отредактирована.');
        }

        /** @var QiwiWallet $qiwiWallet */
        $qiwiWallet = $this->shop->qiwiWallets()->findOrFail($walletId);

        if ($qiwiWallet->qiwiTransactions()->where('status', QiwiTransaction::STATUS_RESERVED)->count() > 0) {
            return redirect('/shop/management/qiwi')->with('flash_warning', 'На данный кошелек есть зарезериврованные платежи. Дождитесь окончания резерва и повторите попытку.');
        }

        $qiwiWallet->delete();
        return redirect('/shop/management/qiwi')->with('flash_success', 'Кошелек успешно удален.');
    }

    public function operations(Request $request)
    {
        \View::share('section', 'operations');

        $defaultStartDate = Carbon::now()->startOfDay()->addDays(-14);
        $defaultEndDate = Carbon::now();
        $firstTransaction = QiwiTransaction::first();
        if ($firstTransaction) {
            $minStartDate = $firstTransaction->paid_at;
        } else {
            $minStartDate = $defaultStartDate;
        }

        list($periodStart, $periodEnd) = $this->parseDayPeriod($request,
            $defaultStartDate, $defaultEndDate, $minStartDate);

        $transactions = QiwiTransaction::with(['qiwiWallet', 'order'])
            ->applySearchFilters($request)
            ->orderBy('paid_at', 'desc')
            ->where('paid_at', '>=', $periodStart)
            ->where('paid_at', '<=', $periodEnd)
            ->get();

        $qiwiStats = collect([
            'count' => 0,
            'total' => 0,
            'wallets' => collect([]),
            'deleted_wallets' => 0
        ]);

        $qiwiWalletsFilter = collect([]);

        foreach ($transactions as $transaction) {
            /** @var QiwiTransaction $transaction */
            if ($transaction->status !== QiwiTransaction::STATUS_PAID) {
                continue;
            }

            $qiwiStats['count'] += 1;
            $qiwiStats['total'] += $transaction->amount;
            $wallet = $transaction->qiwiWallet;
            if ($wallet) {
                if (!isset($qiwiWalletsFilter[$wallet->id])) {
                    $qiwiWalletsFilter[$wallet->id] = $wallet->login;
                }
                if (!isset($qiwiStats['wallets'][$wallet->login])) {
                    $qiwiStats['wallets'][$wallet->login] = 0;
                }
                $qiwiStats['wallets'][$wallet->login] += $transaction->amount;
            } else {
                $qiwiStats['deleted_wallets'] += $transaction->amount;
            }
        }

        $transactionsPaginator = collection_paginate($transactions, 20);
        return view('shop.management.qiwi.operations', [
            'qiwiStats' => $qiwiStats,
            'qiwiWalletsFilter' => $qiwiWalletsFilter,
            'transactions' => $transactionsPaginator,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd
        ]);
    }

    public function showApiForm()
    {
        \View::share('section', 'api');

        return view('shop.management.qiwi.api');
    }

    public function api(Request $request)
    {
        $this->validate($request, [
            'qiwi_api_key' => 'required_with:qiwi_api',
            'qiwi_api_url' => 'required_with:qiwi_api|regex:/^(https?):\/\/([A-Z\d\.-]{2,})\.([A-Z]{2,})(:\d{2,4})?.*?$/i'
        ]);

        $integrationsQiwiApi = $request->has('qiwi_api');

        if ($this->shop->integrations_qiwi_api !== $integrationsQiwiApi) {
            $this->shop->qiwiWallets()->delete();
        }

        if ($this->shop->integrations_qiwi_api_key !== $request->get('qiwi_api_key') ||
            $this->shop->integrations_qiwi_api_url !== $request->get('qiwi_api_url')) {
            // url or key has changed
            $this->shop->update([
                'integrations_qiwi_api_last_response' => NULL,
                'integrations_qiwi_api_last_sync_at' => NULL
            ]);
        }

        $this->shop->update([
            'integrations_qiwi_api' => $request->has('qiwi_api'),
            'integrations_qiwi_api_key' => $request->get('qiwi_api_key'),
            'integrations_qiwi_api_url' => $request->get('qiwi_api_url')
        ]);

        return redirect('/shop/management/qiwi/api')->with('flash_success', 'Настройки сохранены.');
    }

}