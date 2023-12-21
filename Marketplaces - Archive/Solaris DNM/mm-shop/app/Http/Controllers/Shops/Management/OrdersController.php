<?php
/**
 * File: OrdersController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers\Shops\Management;

use App\EmployeesLog;
use App\Events\PositionCreated;
use App\Events\PositionPurchased;
use App\GoodsPosition;
use App\GoodsReview;
use App\Http\Requests\OrderAddAddressRequest;
use App\Income;
use App\MessengerModels\Message;
use App\MessengerModels\Thread;
use App\Order;
use App\Packages\PriceModifier\ReferralPriceModifier;
use App\Packages\Utils\BitcoinUtils;
use App\Packages\Utils\PlanUtils;
use App\QiwiTransaction;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

class OrdersController extends ManagementController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function($request, $next) {
            $this->authorize('management-sections-own-orders');
            return $next($request);
        });

        \View::share('page', 'orders');
    }

    public function index(Request $request)
    {
        $orders = $this->shop->orders()
            ->applySearchFilters($request)
            ->with(['city', 'user', 'good', 'position',
                'user.employee:id' // -20-30 запросов ебучего EncryptableTrait, найденного в интернете
            ])
            ->orderBy('created_at', 'desc');

        if (!\Auth::user()->can('management-sections-orders')) {
            $orders = $orders->where(function($query) {
                $query->whereHas('position', function ($query) { // quests made by employee
                    $query->where('employee_id', \Auth::user()->employee->id);
                });

                if (\Auth::user()->can('management-quests-preorder')) { // employee can make preorders for own
                    $query->orWhere(function ($query)  {
                        $accessibleGoodsIds = $this->shop->goods->filter(function ($good) {
                            return \Auth::user()->can('management-quests-create', $good);
                        })->pluck('id');
                        $query->where('status', Order::STATUS_PREORDER_PAID)
                            ->whereIn('good_id', $accessibleGoodsIds);
                    });
                }
            });
        }

        /*
         * Нахуя, блядь? Что бы что??? Что бы память жрало, конечно же. Спасибо за часовую бомбу. Нам понравилось.
         * $orders = $orders->get();
         */

        $orders = $orders->paginate(20);
        $goods = $orders->pluck('good')->unique()->flatten();
        $cities = $orders->pluck('city')->unique()->flatten();
        $users = $orders->pluck('user')->unique()->flatten();

        return view('shop.management.orders.index', [
            'orders' => $orders,
            'goods' => $goods,
            'cities' => $cities,
            'users' => $users,
        ]);
    }

    public function showOrder(Request $request, $orderId)
    {
        /** @var Order $order */
        $order = $this->shop->orders()->findOrFail($orderId);

        if (!\Auth::user()->can('management-sections-orders')) {
            if ($order->status == Order::STATUS_PREORDER_PAID && $order->good) {
                $this->authorize('management-quests-preorder');
                $this->authorize('management-quests-create', [$order->good, $order->city]);
            } else {
                $position = $order->position;
                if (!$position || $position->employee_id !== \Auth::user()->employee->id) {
                    return abort(403);
                }
            }
        }

        return view('shop.management.orders.order', [
            'order' => $order,
            'preorderTimeExtendSteps' => \App\GoodsPackage::PREORDER_TIME_STEPS,
        ]);
    }

    public function order(OrderAddAddressRequest $request, $orderId)
    {
        $employee = \Auth::user()->employee;

        $position = GoodsPosition::create([
            'good_id' => $request->order->good_id,
            'package_id' => $request->order->package_id,
            'employee_id' => $employee->id,
            'subregion_id' => NULL,
            'custom_place_id' => NULL,
            'quest' => $request->get('quest'),
            'available' => false
        ]);

        $request->order->position_id = $position->id;
        $request->order->status = Order::STATUS_PAID;
        $request->order->save();

        event(new PositionCreated($position));
        event(new PositionPurchased($position, $request->order->user));

        $productPrice = $request->order->getOverallPrice();

        if (!QiwiTransaction::where('order_id', $request->order->id)->exists()) { // preorder paid not by QIWI
            $this->shop->balanceOperation($productPrice, BitcoinUtils::CURRENCY_BTC, 'Продажа товара', ['order_id' => $orderId]);

            if (!empty($request->order->referrer_fee) && ($referrer = $request->order->user->referrer)) {
                $priceWithFee = ReferralPriceModifier::applyFee($request->order->package_price_btc, BitcoinUtils::CURRENCY_BTC, $request->order->referrer_fee);
                $referrerFee = $priceWithFee - $request->order->package_price_btc;
                $referrer->balanceOperation($referrerFee, BitcoinUtils::CURRENCY_BTC, 'Процент с покупки товара рефералом');
            }

            $fee = PlanUtils::getFeeForOrder($this->shop->plan, $productPrice);
            if ($fee && $fee > 0) {
                $this->shop->balanceOperation(-$fee, BitcoinUtils::CURRENCY_BTC, 'Комиссия за продажу', ['order_id' => $orderId]);
                Income::create([
                    'wallet_id' => $this->shop->primaryWallet()->id,
                    'amount_usd' => btc2usd($fee),
                    'amount_btc' => $fee,
                    'description' => 'Комиссия за продажу ' . $orderId
                ]);
            }
        }

        EmployeesLog::log(\Auth::user(), EmployeesLog::ACTION_ORDERS_PREORDER,
            ['order_id' => $request->order->id],
            ['good_title' => $request->order->good_title]);

        return redirect('/shop/management/orders/' . $orderId)->with('flash_success', 'Квест добавлен.');
    }

    public function showReviews(Request $request)
    {
        if (empty($request->get('user'))) {
            $user = null;
            \View::share('section', 'reviews');
        } else {
            $user = User::findOrFail($request->get('user'));
        }

        $reviews = GoodsReview::with(['good', 'order', 'user'])
            ->applySearchFilters($request)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('shop.management.orders.reviews', [
            'reviews' => $reviews,
            'selectedUser' => $user
        ]);
    }

    public function orderNotFound(Request $request, $orderId)
    {
        if ($request->get('_token') !== csrf_token()) {
            throw new TokenMismatchException;
        }

        $order = Order::findOrFail($orderId);

        if (!in_array($order->status, [\App\Order::STATUS_PAID, \App\Order::STATUS_PROBLEM])) {
            return redirect('/shop/management/orders/' . $orderId)->with('flash_warning', 'Отметить ненайденным можно только оплаченные и проблемные заказы.');
        }

        if ($order->courier_fined) {
            return redirect('/shop/management/orders/' . $orderId)->with('flash_warning', 'Курьер уже был оштрафован за этот заказ.');
        }

        if ($order->package && $order->position->employee) {
            $employee = $order->position->employee;
            $employeePenalty = -$order->package->employee_penalty;

            if ($employeePenalty !== 0.0) {
                $employee->earnings()->create([
                    'shop_id' => $order->shop_id,
                    'order_id' => $order->id,
                    'amount' => $employeePenalty,
                    'description' => 'Штраф за ненаход'
                ]);

                $employee->balance -= $employeePenalty;
                $employee->save();

                $order->courier_fined = true;
                $order->save();

                return redirect('/shop/management/orders/' . $orderId)->with('flash_warning', 'К сотруднику применен штраф в размере ' . $employeePenalty . 'р за ненайденный квест.');
            }
        }

        return redirect('/shop/management/orders/' . $orderId)->with('flash_warning', 'Что-то пошло не так. Возможно штраф не указан или равен 0.');
    }

    public function extendPreorderTime(Request $request, $orderId) {
        $order = Order::findOrFail($orderId);

        $thread = Thread::create(['subject' => 'Продление времени предзаказа #' . $order->id, 'order_id' => $order->id]);
        $thread->addParticipant($order->user_id);

        Message::create([
            'thread_id' => $thread->id,
            'user_id' => -$this->shop->id,
            'body' => 'Магазин ' . $this->shop->title . ' предлагает вам продлить время на выполнение предзаказа #'.$order->id.'.',
        ]);

        $extendParams = \Crypt::encrypt([
            'shop_id' => $this->shop->id,
            'thread_id' => $thread->id,
            'extend_time' => $request->get('time')
        ]);

        $extend_url = '{url}/orders/extend/'.$order->id.'?params='.$extendParams;

        Message::create([
            'thread_id' => $thread->id,
            'user_id' => -$this->shop->id,
            'body' => 'Для продления времени на ' . trans_choice('plur.hours', $request->get('time'), ['value' => $request->get('time')]) .
                      ' для выполнения предзаказа нажмите кнопку &laquo;Продлить&raquo;.<br /><a href="'.$extend_url.'" class="btn btn-orange">Продлить</a>',
            'system' => true
        ]);

        return redirect('/shop/management/orders/' . $order->id)->with('flash_success', 'Заявка на продление отправлена.');
    }
}
