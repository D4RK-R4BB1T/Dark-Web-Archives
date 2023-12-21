<?php
/**
 * File: FinancesController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers\Shops\Management;


use App\Employee;
use App\EmployeesLog;
use App\EmployeesPayout;
use App\Http\Requests\ShopFinancesEmployeesPayoutRequest;
use App\Jobs\CreateBitcoinWallet;
use App\Jobs\MakePayout;
use App\Operation;
use App\Packages\Utils\BitcoinUtils;
use App\Providers\DynamicPropertiesProvider;
use App\Wallet;
use Illuminate\Http\Request;

class FinancesController extends ManagementController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function($request, $next) {
            $this->authorize('management-sections-finances');
            return $next($request);
        });

        \View::share('page', 'finances');
    }

    public function index(Request $request)
    {
        \View::share('section', 'index');

        $operations = $this->shop->trashedOperations()
            ->with(['trashedWallet', 'order'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('shop.management.finances.index', [
            'operations' => $operations
        ]);
    }

    public function showAddForm(Request $request)
    {
        if ($this->shop->wallets()->count() >= 10) {
            return redirect('/shop/management/finances')->with('flash_warning', 'Количество кошельков превышает максимальное значение.');
        }

        \View::share('section', 'add');
        return view('shop.management.finances.add');
    }


    public function add(Request $request)
    {
        if ($this->shop->wallets()->count() >= 10) {
            return redirect('/shop/management/finances')->with('flash_warning', 'Количество кошельков превышает максимальное значение.');
        }
        
        $this->validate($request, [
            'title' => 'required|min:3|max:20'
        ]);

        \View::share('section', 'add');

        $job = new CreateBitcoinWallet($this->shop, Wallet::TYPE_ADDITIONAL, ['title' => $request->get('title')]);
        dispatch($job);

        return redirect('/shop/management/finances')->with('flash_success', 'Новый кошелек создается, подождите..');
    }

    public function showEditForm(Request $request, $walletId)
    {
        \View::share('section', 'index');

        /** @var Wallet $wallet */
        $wallet = $this->shop->wallets()->findOrFail($walletId);

        return view('shop.management.finances.edit', [
            'wallet' => $wallet
        ]);
    }

    public function edit(Request $request, $walletId)
    {
        /** @var Wallet $wallet */
        $wallet = $this->shop->wallets()->findOrFail($walletId);

        $this->validate($request, [
            'title' => 'required|min:3|max:20'
        ]);

        $wallet->update([
            'title' => $request->get('title')
        ]);

        return redirect('/shop/management/finances')->with('flash_success', 'Настройки сохранены.');
    }

    public function showDeleteForm(Request $request, $walletId)
    {
        \View::share('section', 'index');

        /** @var Wallet $wallet */
        $wallet = $this->shop->wallets()->findOrFail($walletId);

        if ($wallet->getPendingBalance() > 0) {
            return redirect()->back()->with('flash_warning', 'Имеются транзакции, ожидающие подтверждения. Дождитесь подтверждения транзакций и повторите попытку.');
        }

        return view('shop.management.finances.delete', [
            'wallet' => $wallet
        ]);
    }

    public function delete(Request $request, $walletId)
    {
        /** @var Wallet $wallet */
        $wallet = $this->shop->wallets()->findOrFail($walletId);

        if ($wallet->getPendingBalance() > 0) {
            return redirect()->back()->with('flash_warning', 'Имеются транзакции, ожидающие подтверждения. Дождитесь подтверждения транзакций и повторите попытку.');
        }

        $availableBalance = $wallet->getRealBalance(BitcoinUtils::CURRENCY_BTC);
        if ($availableBalance !== 0.0) {
            $wallet->balanceOperation(-$availableBalance, BitcoinUtils::CURRENCY_BTC, 'Перевод на кошелек');
            $this->shop->primaryWallet()->balanceOperation($availableBalance, BitcoinUtils::CURRENCY_BTC, 'Перевод с кошелька');
        }
        $wallet->delete();

        return redirect('/shop/management/finances')->with('flash_success', 'Кошелек удален.');
    }

    public function view(Request $request, $walletId)
    {
        \View::share('section', 'index');

        /** @var Wallet $wallet */
        $wallet = $this->shop->wallets()->findOrFail($walletId);

        $operations = $wallet->operations()
            ->with(['wallet'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('shop.management.finances.view', [
            'wallet' => $wallet,
            'operations' => $operations
        ]);
    }

    public function showSendForm(Request $request, $walletId)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return redirect('/shop/management/finances')->with('flash_warning', 'Прием платежей временно приостановлен, попробуйте позже.');
        }

        \View::share('section', 'index');

        /** @var Wallet $wallet */
        $wallet = $this->shop->wallets()->findOrFail($walletId);

        return view('shop.management.finances.send', [
            'wallet' => $wallet
        ]);
    }

    public function send(Request $request, $walletId)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return redirect('/shop/management/finances')->with('flash_warning', 'Прием платежей временно приостановлен, попробуйте позже.');
        }

        /** @var Wallet $wallet */
        $wallet = $this->shop->wallets()->findOrFail($walletId);

        // check finances disabled
        if($wallet->shop_id === $this->shop->id && $wallet->type === Wallet::TYPE_PRIMARY && !is_null($this->propertiesProvider->getBool(DynamicPropertiesProvider::KEY_WDRAW_SHOP_WALLET))) {
            return redirect('/shop/management/finances')->with('flash_warning', 'Отправка платежей приостановлена службой безопасности.');
        }

        $this->validate($request, [
            'amount' => 'required|numeric|min:' . config('mm2.bitcoin_min') . '|max:' . min($wallet->getRealBalance(BitcoinUtils::CURRENCY_BTC) - config('mm2.bitcoin_fee'), config('mm2.bitcoin_max')),
            'wallet' => 'required|between:27,34'
        ]);

        $amount = floatval($request->get('amount'));
        $amount += config('mm2.bitcoin_fee');

        $wallet->balanceOperation(-$amount, BitcoinUtils::CURRENCY_BTC, 'Вывод на кошелек (' . $request->get('wallet') .')');

        dispatch(new MakePayout(
            $request->get('wallet'), $amount,
            'FinancesController@send', $request->path(),
            $wallet->id, \Auth::user()->id
        ));

        return redirect('/shop/management/finances')->with('flash_success', 'Перевод поставлен в очередь на выплату.');
    }

    public function employee(Request $request, $employeeId)
    {
        /** @var Employee $employee */
        $employee = $this->shop->employees()->findOrFail($employeeId);

        $earnings = null;
        $payouts = null;

        if ($request->get('show', 'earnings') === 'earnings') {
            $earnings = $employee->earnings()->orderBy('created_at', 'desc')->paginate(20);
        } else {
            $payouts = $employee->payouts()->with(['operation', 'senderEmployee'])->orderBy('id', 'desc')->paginate(20);
        }

        return view('shop.management.finances.employee.index', [
            'employee' => $employee,
            'earnings' => $earnings,
            'payouts' => $payouts
        ]);
    }

    public function showEmployeePayoutForm(Request $request, $employeeId)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return redirect('/shop/management/finances/employee/' . $employeeId)->with('flash_warning', 'Прием платежей временно приостановлен, попробуйте позже.');
        }

        if (!is_null($this->propertiesProvider->getBool(DynamicPropertiesProvider::KEY_WDRAW_SHOP_WALLET))) {
            return redirect('/shop/management/finances')->with('flash_warning', 'Отправка платежей приостановлена службой безопасности.');
        }

        /** @var Employee $employee */
        $employee = $this->shop->employees()->findOrFail($employeeId);
        $wallets = $this->shop->wallets()->get();

        return view('shop.management.finances.employee.payout', [
            'employee' => $employee,
            'wallets' => $wallets
        ]);
    }

    public function employeePayout(ShopFinancesEmployeesPayoutRequest $request, $employeeId)
    {
        if (!is_null($this->propertiesProvider->getBool(DynamicPropertiesProvider::KEY_WDRAW_SHOP_WALLET))) {
            return redirect('/shop/management/finances')->with('flash_warning', 'Отправка платежей приостановлена службой безопасности.');
        }

        $request->employee->balance -= $request->get('amount');
        $request->employee->save();

        /** @var Operation $operation */
        $operation = $request->employee->user->balanceOperation($request->get('amount'), BitcoinUtils::CURRENCY_RUB, 'Выплата средств');

        $request->wallet->balanceOperation(-$request->get('amount'), BitcoinUtils::CURRENCY_RUB, 'Выплата средств сотруднику (' . $request->employee->user->getPublicName() . ')');

        EmployeesPayout::create([
            'shop_id' => $this->shop->id,
            'employee_id' => $request->employee->id,
            'sender_employee_id' => \Auth::user()->employee->id,
            'operation_id' => $operation->id,
            'description' => 'Выплата средств сотруднику'
        ]);

        EmployeesLog::log(\Auth::user(), EmployeesLog::ACTION_FINANCE_PAYOUT, [],
            ['employee_id' => $request->employee->id]);

        return redirect('/shop/management/finances/employee/' . $employeeId)->with('flash_success', 'Выплата произведена.');
    }

    public function employeeAll(Request $request)
    {
        \View::share('section', 'all');

        $earnings = null;
        $payouts = null;

        if ($request->get('show', 'earnings') === 'earnings') {
            $earnings = $this->shop->employeesEarnings()->with(['employee', 'employee.user'])->orderBy('created_at', 'desc')->paginate(20);
        } else {
            $payouts = $this->shop->employeesPayouts()->with(['operation', 'employee', 'employee.user', 'senderEmployee', 'senderEmployee.user'])->orderBy('id', 'desc')->paginate(20);
        }

        return view('shop.management.finances.employee.all', [
            'earnings' => $earnings,
            'payouts' => $payouts
        ]);
    }
}