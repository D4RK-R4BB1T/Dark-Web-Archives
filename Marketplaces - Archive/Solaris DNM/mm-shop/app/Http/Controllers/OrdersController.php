<?php
/**
 * File: OrdersController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers;


use App\Events\OrderFinished;
use App\GoodsReview;
use App\Http\Requests\OrderReviewRequest;
use App\MessengerModels\Message;
use App\MessengerModels\Participant;
use App\MessengerModels\Thread;
use App\Order;
use App\Shop;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
        \View::share('page', 'orders');
    }

    public function index(Request $request)
    {
        /** @var Collection|Order[] $orders */
        $orders = \Auth::user()->orders()
            ->with(['shop', 'city', 'good'])
            ->applySearchFilters($request)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        /*
         * зачем
        $_request = clone $request;
        $allOrders = \Auth::user()->orders()
            ->applySearchFilters($_request)
            ->with(['city', 'good'])
            ->orderBy('created_at', 'desc')
            ->get();
        $goodsFilter = $allOrders->pluck('good_title', 'good_id');
        $citiesFilter = $allOrders->pluck('city.title', 'city.id');*/

        $goods = $orders->pluck('good')->unique()->flatten();
        $cities = $orders->pluck('city')->unique()->flatten();

        return view('orders.index', [
            'orders' => $orders,
            'goods' => $goods,
            'cities' => $cities
        ]);
    }

    public function order(Request $request, $orderId)
    {
        /** @var Order $order */
        $order = \Auth::user()->orders()->findOrFail($orderId);
        return view('orders.order', [
            'order' => $order
        ]);
    }

    public function orderQiwiPaid(Request $request, $orderId)
    {
        /** @var Order $order */
        $order = \Auth::user()->orders()->findOrFail($orderId);

        if ($order->status !== Order::STATUS_QIWI_RESERVED) {
            \App::abort(403);
        }

        $order->status = Order::STATUS_QIWI_PAID;
        $order->save();

        return redirect('/orders/' . $orderId, 303)->with('flash_success', 'Заказ отправлен на проверку.');
    }

    public function showReviewForm(Request $request, $orderId)
    {
        /** @var Order $order */
        $order = \Auth::user()->orders()->findOrFail($orderId);
        if ($order->review) {
            \App::abort(404);
        }

        if ($order->status !== Order::STATUS_PROBLEM && $order->status !== Order::STATUS_PAID) {
            \App::abort(404);
        }

        return view('orders.review', [
            'order' => $order
        ]);
    }

    public function review(OrderReviewRequest $request, $orderId)
    {
        $review = GoodsReview::create([
            'good_id' => $request->order->good_id,
            'user_id' => \Auth::user()->id,
            'order_id' => $request->order->id,
            'city_id' => $request->order->city_id,
            'text' => $request->get('text'),
            'shop_rating' => $request->get('shop_rating'),
            'dropman_rating' => $request->get('dropman_rating'),
            'item_rating' => $request->get('item_rating')
        ]);

        $request->order->review_id = $review->id;
        $request->order->status = Order::STATUS_FINISHED;
        $request->order->save();

        event(new OrderFinished($request->order));

        if ($request->order->thread)
        {
            Message::create([
                'thread_id' => $request->order->thread->id,
                'user_id' => \Auth::user()->id,
                'body' => 'Пользователь оставил отзыв и отметил заказ как завершенный.',
                'system' => true
            ]);

            $request->order->thread->markAsRead(\Auth::user()->id);
        }

        return redirect('/orders/' . $request->order->id, 303)->with('flash_success', 'Отзыв оставлен.');
    }

    public function showProblemForm(Request $request, $orderId)
    {
        /** @var Order $order */
        $order = \Auth::user()->orders()->findOrFail($orderId);
        if ($order->status !== Order::STATUS_PAID) {
            \App::abort(404);
        }

        return view('orders.problem', [
            'order' => $order
        ]);
    }

    public function problem(Request $request, $orderId)
    {
        /** @var Order $order */
        $order = \Auth::user()->orders()->findOrFail($orderId);
        if ($order->status !== Order::STATUS_PAID) {
            \App::abort(404);
        }

        $order->status = Order::STATUS_PROBLEM;
        $order->status_was_problem = true;
        $order->save();

        /** @var Thread $thread */
        $thread = Thread::create([
            'subject' => 'Проблема с заказом #' . $orderId,
            'order_id' => $order->id
        ]);

        Message::create([
            'thread_id' => $thread->id,
            'user_id' => \Auth::user()->id,
            'body' => 'Пользователь отметил заказ как проблемный.',
            'system' => true
        ]);

        Participant::create([
            'thread_id' => $thread->id,
            'user_id' => \Auth::user()->id,
            'last_read' => new Carbon()
        ]);

        $thread->addParticipant(-$order->shop_id);

        if (($employee = $order->position->employee) && $employee->quests_autojoin) {
            $thread->addParticipant($employee->user->id);

            Message::create([
                'thread_id' => $thread->id,
                'user_id' => \Auth::user()->id,
                'body' => 'Сотрудник ' . $employee->user->getPublicName() . ' автоматически добавлен в диалог.',
                'system' => true
            ]);
        }

        return redirect('/messages/' . $thread->id, 303)->with('flash_success', 'Сообщение создано!');
    }

    public function acceptPreorderTimeExtend(Request $request, $orderId)
    {
        $user = \Auth::user();

        try {
            $params = \Crypt::decrypt($request->get('params'));
        } catch (\Exception $exception) {
            abort(403);
        }

        Shop::findOrFail($params['shop_id']);
        $thread = Thread::findOrFail($params['thread_id']);
        $order = Order::where('shop_id', '=', $params['shop_id'])->findOrFail($orderId);

        if ($user->id !== $order->user_id || $order->status !== 'preorder_paid' || $params['extend_time'] <= 0)
            abort(403);

        $order->package_preorder_time += $params['extend_time'];
        $order->save();
        $thread->delete();
        return redirect('/orders/' . $order->id)->with('flash_success', 'Время на подготовку предзаказа продлено.');
    }

    public function showReviewEditForm($orderId)
    {
        $order = \Auth::user()->orders()->findOrFail($orderId);

        if(!$review = $order->review) {
            return redirect('/orders/' . $order->id)->with('flash_warning', 'Вы не оставляли отзыв.');
        }

        if ($review->getEditRemainingTime() < 0) {
            return redirect('/orders/' . $order->id)->with('flash_warning', 'Больше отзыв редактировать нельзя.');
        }

        if ($review->getLastEditTime() < config('mm2.review_edit_time_every')) {
            return redirect('/orders/' . $order->id)->with('flash_warning', 'Вам нужно подождать прежде чем редактировать отзыв.');
        }

        return view('orders.review.edit', [
            'order' => $order,
            'review' => $review
        ]);
    }

    public function reviewEdit(OrderReviewRequest $request, $orderId)
    {
        $order = \Auth::user()->orders()->findOrFail($orderId);

        if(!$review = $order->review) {
            abort(403);
        }

        if ($review->getEditRemainingTime() < 0) {
            abort(403);
        }

        if ($review->getLastEditTime() < config('mm2.review_edit_time_every')) {
            abort(403);
        }

        $review->text = $request->get('text');
        $review->shop_rating = $request->get('shop_rating');
        $review->dropman_rating = $request->get('dropman_rating');
        $review->item_rating = $request->get('item_rating');
        $review->save();

        return redirect('/orders/' . $orderId, 303)->with('flash_success', 'Отзыв сохранен.');
    }
}
