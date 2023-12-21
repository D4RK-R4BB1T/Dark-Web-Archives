<?php
/**
 * File: StatsController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers\Shops\Management;


use App\AccountingDistribution;
use App\AccountingLot;
use App\Good;
use App\GoodsPackage;
use App\GoodsPosition;
use App\Http\Requests\ShopStatsAccountingAddRequest;
use App\Http\Requests\ShopStatsAccountingDistributionAddRequest;
use App\Http\Requests\ShopStatsAccountingDistributionEditRequest;
use App\Http\Requests\ShopStatsAccountingEditRequest;
use App\Order;
use App\Packages\Utils\BitcoinUtils;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatsController extends ManagementController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function($request, $next) {
            $this->authorize('management-sections-stats');
            return $next($request);
        });

        \View::share('page', 'stats');
    }

    public function index(Request $request)
    {
        return redirect('/shop/management/stats/users');
    }

    public function users(Request $request)
    {
        \View::share('section', 'users');
        $usersCount = User::count();
        $users = User::applySearchFilters($request)
            ->orderBy('last_login_at', 'desc')
            ->paginate(10);

        return view('shop.management.stats.users.index', [
            'usersCount' => $usersCount,
            'users' => $users
        ]);
    }

    public function orders(Request $request)
    {
        \View::share('section', 'orders');

        $defaultStartDate = Carbon::now()->startOfDay()->addDays(-14);
        $defaultEndDate = Carbon::now();
        $minStartDate = Carbon::now()->addMonths(-2)->startOfDay();

        list($periodStart, $periodEnd) = $this->parseDayPeriod($request,
            $defaultStartDate, $defaultEndDate, $minStartDate);

        $orders = $this->shop->orders()->where('status', '!=', Order::STATUS_QIWI_RESERVED)
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<=', $periodEnd)
            ->with(['good', 'city'])
            ->get();

        $totalOrdersStats = new \stdClass;
        $totalOrdersStats->count = 0;
        $totalOrdersStats->total = [
            BitcoinUtils::CURRENCY_BTC => 0,
            BitcoinUtils::CURRENCY_RUB => 0,
            BitcoinUtils::CURRENCY_USD => 0
        ];
        $totalOrdersStats->total_btc = 0;
        $totalOrdersStats->problems_count = 0;
        $totalOrdersStats->problems_avg = 0;

        $ordersStats = [];
        foreach ($orders as $order) {
            $goodTitle = $order->good_title;
            if (!isset($ordersStats[$goodTitle])) {
                $ordersStats[$goodTitle] = new \stdClass;
                $ordersStats[$goodTitle]->good = $order->good;
                $ordersStats[$goodTitle]->good_id = $order->good_id;
                $ordersStats[$goodTitle]->good_title = $order->good_title;
                $ordersStats[$goodTitle]->city = $order->city;
                $ordersStats[$goodTitle]->count = 0;
                $ordersStats[$goodTitle]->measures = [
                    GoodsPackage::MEASURE_GRAM => 0,
                    GoodsPackage::MEASURE_PIECE => 0,
                    GoodsPackage::MEASURE_ML => 0,
                ];
                $ordersStats[$goodTitle]->total = [
                    BitcoinUtils::CURRENCY_BTC => 0,
                    BitcoinUtils::CURRENCY_RUB => 0,
                    BitcoinUtils::CURRENCY_USD => 0
                ];
                $ordersStats[$goodTitle]->total_btc = 0;
                $ordersStats[$goodTitle]->problems_count = 0;

            }

            $ordersStats[$goodTitle]->count += 1;
            $ordersStats[$goodTitle]->measures[$order->package_measure] += $order->package_amount;
            $ordersStats[$goodTitle]->total[$order->package_currency] += $order->package_price;
            $ordersStats[$goodTitle]->total_btc += $order->package_price_btc;
            $ordersStats[$goodTitle]->problems_count += ($order->status_was_problem) ? 1 : 0;

            $totalOrdersStats->count += 1;
            $totalOrdersStats->total[$order->package_currency] += $order->package_price;
            $totalOrdersStats->total_btc += $order->package_price_btc;
            $totalOrdersStats->problems_count += ($order->status_was_problem) ? 1 : 0;
        }

        $ordersStats = collect($ordersStats)->sortByDesc('count');
        $totalOrdersStats->problems_avg = $totalOrdersStats->problems_count / max($totalOrdersStats->problems_count, 1) * 100;

        return view('shop.management.stats.orders.index', [
            'totalOrdersStats' => $totalOrdersStats,
            'ordersCount' => count($orders),
            'ordersSum' => $ordersStats->sum('total_btc'),
            'ordersProblemAvg' => ($ordersStats->sum('problems_count')) / max(count($orders), 1) * 100,
            'ordersStats' => $ordersStats,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd
        ]);
    }

    public function accounting(Request $request)
    {
        \View::share('section', 'accounting');
        $goods = $this->shop->goods()
            ->whereIn('id', $this->shop->lots()->pluck('good_id'))
            ->get(['title', 'id']);

        $lots = $this->shop->lots()
            ->applySearchFilters($request)
            ->with(['good', 'distributions', 'distributions.employee'])
            ->paginate(20);

        $lotsStats = [];
        $lotsTotalStats = [
            GoodsPackage::MEASURE_GRAM => 0,
            GoodsPackage::MEASURE_PIECE => 0,
            GoodsPackage::MEASURE_ML => 0,
        ];

        foreach ($lots as $lot) {
            /** @var AccountingLot $lot */
            $goodId = $lot->good_id;
            if (!isset($lotsStats[$goodId])) {
                $lotsStats[$goodId] = new \stdClass;
                $lotsStats[$goodId]->good_title = traverse($lot, 'good->title') ?: '-';
                $lotsStats[$goodId]->measures = [
                    GoodsPackage::MEASURE_GRAM => 0,
                    GoodsPackage::MEASURE_PIECE => 0,
                    GoodsPackage::MEASURE_ML => 0,
                ];
            }

            $lotsStats[$goodId]->measures[$lot->measure] += $lot->available_amount;
            $lotsTotalStats[$lot->measure] += $lot->available_amount;
        }

        return view('shop.management.stats.accounting.index', [
            'goods' => $goods,
            'lotsStats' => collect($lotsStats),
            'lotsTotalStats' => collect($lotsTotalStats),
            'lots' => $lots
        ]);
    }

    public function showAccountingAddForm(Request $request)
    {
        \View::share('section', 'accounting');
        $goods = $this->shop->goods()->get();

        return view('shop.management.stats.accounting.lots.add', [
            'goods' => $goods
        ]);
    }

    public function accountingAdd(ShopStatsAccountingAddRequest $request)
    {
        $lot = AccountingLot::create([
            'shop_id' => $this->shop->id,
            'good_id' => $request->get('good'),
            'amount' => $request->get('amount'),
            'unused_amount' => $request->get('amount'),
            'available_amount' => $request->get('amount'),
            'price' => $request->get('price'),
            'currency' => $request->get('currency'),
            'measure' => $request->get('measure'),
            'note' => $request->get('note')
        ]);

        return redirect('/shop/management/stats/accounting')->with('flash_success', 'Партия успешно добавлена.');
    }

    public function showAccountingEditForm(Request $request, $lotId)
    {
        \View::share('section', 'accounting');
        $lot = $this->shop->lots()->findOrFail($lotId);

        return view('shop.management.stats.accounting.lots.edit', [
            'lot' => $lot
        ]);
    }

    public function accountingEdit(ShopStatsAccountingEditRequest $request, $lotId)
    {
        $amountDiff = doubleval($request->get('amount')) - $request->lot->amount;
        $request->lot->amount = $request->get('amount');
        $request->lot->unused_amount += $amountDiff;
        $request->lot->available_amount += $amountDiff;
        $request->lot->price = $request->get('price');
        $request->lot->currency = $request->get('currency');
        $request->lot->measure = $request->get('measure');
        $request->lot->note = $request->get('note');
        $request->lot->save();

        return redirect('/shop/management/stats/accounting')->with('flash_success', 'Партия успешно отредактирована.');
    }

    public function accountingDistributions(Request $request, $lotId)
    {
        \View::share('section', 'accounting');

        /** @var AccountingLot $lot */
        $lot = $this->shop->lots()->findOrFail($lotId);

        $distributions = $lot->distributions()
            ->with(['employee', 'employee.user'])
            ->get();

        return view('shop.management.stats.accounting.distributions.index', [
            'lot' => $lot,
            'distributions' => $distributions
        ]);
    }

    public function showAccountingDistributionsAddForm(Request $request, $lotId)
    {
        \View::share('section', 'accounting');

        /** @var AccountingLot $lot */
        $lot = $this->shop->lots()->findOrFail($lotId);

        $employees = $this->shop->employees()->with(['user'])->get();

        return view('shop.management.stats.accounting.distributions.add', [
            'lot' => $lot,
            'employees' => $employees
        ]);
    }

    public function accountingDistributionAdd(ShopStatsAccountingDistributionAddRequest $request, $lotId)
    {
        /** @var AccountingDistribution $distribution */
        $distribution = $request->lot->distributions()->firstOrNew([
            'employee_id' => $request->get('employee')
        ]);

        $distribution->amount = ($distribution->amount ?: 0) + doubleval($request->get('amount'));
        $distribution->available_amount = ($distribution->available_amount ?: 0) + $request->get('amount');
        $distribution->proceed_btc = $distribution->proceed_btc ?: 0;
        $distribution->note = $request->get('note');
        $distribution->save();

        $request->lot->unused_amount -= $request->get('amount');
        $request->lot->save();

        return redirect('/shop/management/stats/accounting/' . $lotId)->with('flash_success', 'Товар успешно выдан.');
    }

    public function showAccountingDistributionsEditForm(Request $request, $lotId, $distributionId)
    {
        \View::share('section', 'accounting');

        /** @var AccountingLot $lot */
        $lot = $this->shop->lots()->findOrFail($lotId);

        /** @var AccountingDistribution $distribution */
        $distribution = $lot->distributions()->findOrFail($distributionId);

        return view('shop.management.stats.accounting.distributions.edit', [
            'lot' => $lot,
            'distribution' => $distribution
        ]);
    }

    public function accountingDistributionsEdit(ShopStatsAccountingDistributionEditRequest $request, $lotId, $distributionId)
    {
        $amountDiff = doubleval($request->get('amount')) - $request->distribution->amount;
        $request->distribution->amount = $request->get('amount');
        $request->distribution->available_amount += $amountDiff;
        $request->distribution->note = $request->get('note');
        $request->distribution->save();

        $request->lot->unused_amount -= $amountDiff;
        $request->lot->save();

        return redirect('/shop/management/stats/accounting/' . $lotId)->with('flash_success', 'Выданный товар успешно отредактирован.');
    }

    public function filling()
    {
        \View::share('section', 'filling');

        $goods = $this->shop->goods()
            ->with(['cities', 'packages', 'packages.availablePositions'])
            ->get();

        $stats = collect();
        $totalStats = collect([
            'goods_count' => 0,
            'quests_count' => 0,
            'quests_total' => collect([
                BitcoinUtils::CURRENCY_BTC => 0,
                BitcoinUtils::CURRENCY_RUB => 0,
                BitcoinUtils::CURRENCY_USD => 0
            ])
        ]);


        foreach ($goods as $good)
        {
            $packages = $good->packages->filter(function($package) {
                return !$package->preorder; // no need to preorder packages
            })->sortBy('amount');

            if ($packages->count() === 0) {
                continue;
            }

            $totalStats['goods_count'] += 1;

            /** @var Good $good */
            $item = collect([
                'id' => $good->id,
                'title' => $good->title,
                'city' => $good->cities->map(function($city) { return $city->title; })->implode(', '),
                'packages_count' => $packages->count(),
                'packages' => $packages->map(function ($package) {
                    return $package->getHumanWeight() . ' - ' . $package->getHumanPrice();
                })->implode(chr(10)), // new line for tooltip
                'quests_count' => 0,
                'quests' => collect(),
                'quests_total' => collect([
                    BitcoinUtils::CURRENCY_BTC => 0,
                    BitcoinUtils::CURRENCY_RUB => 0,
                    BitcoinUtils::CURRENCY_USD => 0
                ])
            ]);

            foreach ($packages as $package) {
                /** @var GoodsPackage $package */
                $questsCount = $package->availablePositions->count();
                $item['quests'][] = sprintf("%s - %s %s",
                    $package->getHumanWeight(), $questsCount, plural($questsCount, ['квест', 'квеста', 'квестов']));
                $item['quests_count'] += $questsCount;
                $totalStats['quests_count'] += $questsCount;
                $item['quests_total'][$package->currency] += $package->price * $questsCount;
                $totalStats['quests_total'][$package->currency] += $package->price * $questsCount;
            }

            $item['quests'] = $item['quests']->implode(chr(10)); // new line for tooltip
            $item['quests_total'] = $item['quests_total']->filter(function ($value) {
                return $value !== 0; // remove empty values
            })->map(function($amount, $currency) {
                return human_price($amount, $currency);
            })->implode(', ');

            $stats[] = $item;
        }

        $totalStats['quests_total'] = $totalStats['quests_total']->filter(function ($value) {
            return $value !== 0; // remove empty values
        })->map(function($amount, $currency) {
            return human_price($amount, $currency);
        })->implode(', ');

        //dump($goods);
        return view('shop.management.stats.filling.index', [
            'stats' => $stats,
            'totalStats' => $totalStats
        ]);
    }

    public function employees(Request $request)
    {
        \View::share('section', 'employees');
        $stats = collect([]);
        $employee = collect([]);
        $employees = $this->shop->employees()->with(['user'])->get();
        $defaultStartDate = Carbon::now()->startOfDay()->addDays(-7);
        $defaultEndDate = Carbon::now();
        $minStartDate = Carbon::now()->addMonths(-config('mm2.min_keep_stats_months'))->startOfDay();

        list($periodStart, $periodEnd) = $this->parseDayPeriod($request,
            $defaultStartDate, $defaultEndDate, $minStartDate);

        if($request->get('employee')) {
            $employee = $this->shop->employees()->with(['user'])->findOrFail($request->get('employee'));
            $stats = $employee->getStats($periodStart, $periodEnd);
        }

        return view('shop.management.stats.employees.index', [
            'employees' => $employees,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'employee' => $employee,
            'stats' => $stats,
        ]);
    }
}