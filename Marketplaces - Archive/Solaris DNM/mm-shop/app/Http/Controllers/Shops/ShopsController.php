<?php
/**
 * File: ShopsController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 * 2016.
 */

namespace App\Http\Controllers\Shops;

use App\City;
use App\Employee;
use App\EmployeesLog;
use App\Events\PositionPurchased;
use App\Good;
use App\GoodsPackage;
use App\GoodsPosition;
use App\Http\Controllers\Controller;
use App\Http\Requests\ShopBuyRequest;
use App\Income;
use App\MessengerModels\Message;
use App\MessengerModels\Participant;
use App\MessengerModels\Thread;
use App\Order;
use App\OrdersService;
use App\Packages\PriceModifier\PriceModifierService;
use App\Packages\Utils\BitcoinUtils;
use App\Page;
use App\PaidService;
use App\Promocode;
use App\Providers\DynamicPropertiesProvider;
use App\QiwiTransaction;
use App\Shop;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class ShopsController extends Controller
{
    /** @var Shop $shop */
    private $shop;
    protected $propertiesProvider;

    public function __construct(Request $request)
    {
        parent::__construct();

        $this->shop = Shop::getDefaultShop();
        $this->propertiesProvider = \App::make(DynamicPropertiesProvider::class);
        $this->propertiesProvider->register($this->shop->id);

        if (!$this->shop->guest_enabled) {
            $this->middleware('auth');
        }

        \View::share('section', 'shops');
        \View::share('page', 'shop');

        $this->middleware(function($request, $next) {
            if (!$this->shop->enabled) { // if current user is shop owner, redirect him to shop creation page
                if (\Auth::check() && \Auth::user()->id === $this->shop->owner()->id) {
                    return redirect('/shop/management/init');
                }
            }

            if ($this->shop->isExpired() && $this->shop->plan !== Shop::PLAN_FEE) {
                return response()->view('shop.unpaid');
            }

            if (!is_null($this->propertiesProvider->getBool(DynamicPropertiesProvider::KEY_ENABLED))) {
                \View::share('shop', $this->shop);
                return response()->view('shop.security-service-disabled');
            }

            return $next($request);
        });

        \View::share('shop', $this->shop);
    }

    /**
     * Shows main page of the shop
     * @param Request $request
     * @return mixed
     */
    public function shop(Request $request)
    {
        \View::share('page', 'catalog');

        /** @var Good $goods */
        $goods = $this->shop->availableGoods()
            ->applySearchFilters($request)
            ->with(['cities', 'availablePackages'])
            ->orderBy('priority', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('shop.shop', [
            'goods' => $goods
        ]);
    }

    public function page(Request $request, $slug, $pageId)
    {
        $page = $this->shop->pages()->findOrFail($pageId);

        return view('shop.page', [
            'page' => $page
        ]);
    }

    public function showPageAddForm(Request $request, $slug)
    {
        if (!\Auth::user()->employee) {
            return abort(403);
        }

        $this->authorize('management-sections-pages');

        return view('shop.page-add');
    }

    public function pageAdd(Request $request, $slug)
    {
        if (!\Auth::user()->employee) {
            return abort(403);
        }

        $this->authorize('management-sections-pages');

        $this->validate($request, [
            'title' => 'required|min:5|max:30',
            'body' => 'required|min:5|max:10000'
        ]);

        $page = Page::create([
            'shop_id' => $this->shop->id,
            'title' => $request->get('title'),
            'body' => $request->get('body')
        ]);

        EmployeesLog::log(\Auth::user(), EmployeesLog::ACTION_SETTINGS_PAGE_ADD,
            ['page_id' => $page->id],
            ['page_title' => $page->title]);

        return redirect('/shop/' . $slug . '/pages/' . $page->id)->with('flash_success', 'Страница успешно создана!');
    }

    public function showPageEditForm(Request $request, $slug, $pageId)
    {
        if (!\Auth::user()->employee) {
            return abort(403);
        }

        $this->authorize('management-sections-pages');

        $page = $this->shop->pages()->findOrFail($pageId);

        return view('shop.page-edit', [
            'page' => $page
        ]);
    }

    public function pageEdit(Request $request, $slug, $pageId)
    {
        if (!\Auth::user()->employee) {
            return abort(403);
        }

        $this->authorize('management-sections-pages');

        $this->validate($request, [
            'title' => 'required|min:5|max:30',
            'body' => 'required|min:5|max:10000'
        ]);

        $page = $this->shop->pages()->findOrFail($pageId);

        $page->update([
            'title' => $request->get('title'),
            'body' => $request->get('body')
        ]);

        EmployeesLog::log(\Auth::user(), EmployeesLog::ACTION_SETTINGS_PAGE_EDIT,
            ['page_id' => $page->id],
            ['page_title' => $page->title]);

        return redirect('/shop/' . $slug . '/pages/' . $page->id)->with('flash_success', 'Страница успешно отредактирована!');
    }

    public function showPageDeleteForm(Request $request, $slug, $pageId)
    {
        if (!\Auth::user()->employee) {
            return abort(403);
        }

        $this->authorize('management-sections-pages');

        $page = $this->shop->pages()->findOrFail($pageId);

        return view('shop.page-delete', [
            'page' => $page
        ]);
    }

    public function pageDelete(Request $request, $slug, $pageId)
    {
        if (!\Auth::user()->employee) {
            return abort(403);
        }

        $this->authorize('management-sections-pages');

        $page = $this->shop->pages()->findOrFail($pageId);

        EmployeesLog::log(\Auth::user(), EmployeesLog::ACTION_SETTINGS_PAGE_DELETE,
            ['page_id' => $page->id],
            ['page_title' => $page->title]);

        $page->delete();
        return redirect('/shop/' . $slug)->with('flash_success', 'Страница успешно удалена!');
    }

    public function good(Request $request, $slug, $goodId)
    {

        if (\Auth::check() && \Auth::user()->employee && \Auth::user()->can('management-goods-create')) {
            /** @var Good $good */
            $good = $this->shop->goods()->findOrFail($goodId);
            if ($good->availablePackages()->count() == 0) {
                \View::share('flash_warning', 'Данный товар не отображается в магазине. Необходимо добавить квесты или создать упаковки с типом "предзаказ".');
            }
        } else {
            /** @var Good $good */
            $good = $this->shop->availableGoods()->findOrFail($goodId);
        }

        $cityId = $request->get('city');

        /** @var GoodsPackage[]|Builder $packages */
        $packages = $good->availablePackages();

        /** @var City|null $selectedCity */
        $selectedCity = null;

        if (is_numeric($cityId) && $cityId > 0) {
            $selectedCity = City::findOrFail($cityId);
        }

        $packages = $packages
            ->groupBy(['city_id', 'amount', 'measure', 'preorder'])
            ->with(['city', 'availablePositions', 'availablePositions.region', 'availablePositions.customPlace'])
            ->orderBy('amount')
            ->orderBy('measure')
            ->get();

        $availableCities = $packages
            ->map(function($package) { return $package->city; })
            ->unique('id')
            ->sortByDesc('priority');

        $hasOtherCities = $availableCities->count() > 1;
        if ($availableCities->count() == 1) {
            $selectedCity = $availableCities->first();
            $cityId = $selectedCity->id;
        }

        if (!is_null($cityId)) {
            $packages = $packages->filter(function ($package) use ($cityId) {
                return $package->city_id == $cityId;
            });
        }

        $reviews = $good->reviews();

        if (!\Auth::check() || !\Auth::user()->employee) {
            $reviews = $reviews->where('hidden', false);
        }

        $reviews_city_id = $request->get('review_city');
        $reviews = $reviews->with(['user', 'city'])
            ->when(!empty($reviews_city_id), function ($query) use($reviews_city_id) {
                // filter reviews by city_id if city arg set
                return $query->where('city_id', '=', $reviews_city_id);
            })->orderBy('created_at', 'desc')->paginate(10);

        return view('shop.good', [
            'good' => $good,
            'cityId' => $cityId,
            'availableCities' => $availableCities,
            'selectedCity' => $selectedCity,
            'hasOtherCities' => $hasOtherCities,
            'packages' => $packages,
            'reviews' => $reviews,
            'cities' => City::allCached()
        ]);
    }

    public function showBuyForm(Request $request, $slug, $goodId, $packageId)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return redirect('/shop/' . $slug . '/goods/' . $goodId)->with('flash_warning', 'Прием платежей временно приостановлен, попробуйте позже.');
        }

        /** @var Good $good */
        $good = $this->shop
            ->availableGoods()
            ->findOrFail($goodId);

        /** @var GoodsPackage $package */
        $package = $good->availablePackages()
            ->findOrFail($packageId);

        $position = null;
        if (!$package->preorder) {
            if (!empty($subregionId = $request->get('subregion_id'))) {
                $position = $package->availablePositions()->where('subregion_id', $subregionId)->firstOrFail();
            } elseif (!empty($customPlaceId = $request->get('custom_place_id'))) {
                $position = $package->availablePositions()->where('custom_place_id', $customPlaceId)->firstOrFail();
            } else {
                $position = $package->availablePositions()->firstOrFail();
            }
        }

        $isRiskyOperation = $package->getPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) >= 100000;
        $isRiskAccepted = boolval($request->get('accepted', false));

        if ($isRiskyOperation && !$isRiskAccepted) {
            return view('shop.buy_risks', [
                'good' => $good,
                'package' => $package
            ]);
        }

        return view('shop.buy', [
            'good' => $good,
            'package' => $package,
            'position' => $position,
            'promocode' => null
        ]);
    }

    public function buy(ShopBuyRequest $request, $slug, $goodId, $packageId)
    {
        /** @var Good $good */
        $good = $this->shop->availableGoods()->findOrFail($goodId);
        /** @var GoodsPackage $package */
        $package = $good->availablePackages()->findOrFail($packageId);

        $useQiwi = $request->has('qiwi')
            && ($this->shop->qiwiWallets()->availableForPackage($package)->firstOrFail() !== null);

        $guarantee = false; // (bool) $this->get('guarantee', false);
        $promocode = Promocode::whereCode(trim($request->get('promocode', '')))->first();

        $price = $guarantee
            ? $package->getPriceWithGuaranteeFee(BitcoinUtils::CURRENCY_BTC)
            : $package->getPrice(BitcoinUtils::CURRENCY_BTC, [PriceModifierService::REFERRAL_MODIFIER]);

        $position = null;
        $preorderComment = null;
        $preorderServices = [];

        if (!$package->preorder) {
            // find position
            $position = $package->availablePositions();
            if (!empty($subregionId = $request->get('subregion_id'))) {
                $position = $position->where('subregion_id', $subregionId);
            } elseif (!empty($customPlaceId = $request->get('custom_place_id'))) {
                $position = $position->where('custom_place_id', $customPlaceId);
            }
            $position = $position->lockForUpdate()->firstOrFail();
        } else {
            $preorderComment = $request->get('comment', '');
            // find paid services
            if (count($services = $request->get('services', [])) > 0) {
                foreach ($services as $serviceId) {
                    /** @var PaidService $service */
                    $service = PaidService::where('shop_id', $this->shop->id)->findOrFail($serviceId);
                    $price += $service->getPrice(BitcoinUtils::CURRENCY_BTC);
                    $preorderServices[] = $service;
                }
            }
        }

        if ($request->has('apply_code')) {
            if (!$promocode || !$promocode->isActive()) {
                \Session::flash('flash_warning', 'Промокод не найден!');
            }
            \Session::flashInput($request->input());
            return view('shop.buy', [
                'good' => $good,
                'package' => $package,
                'position' => $position,
                'promocode' => $promocode
            ]);
        }

        $price = $guarantee
            ? $package->getPriceWithGuaranteeFee(BitcoinUtils::CURRENCY_BTC)
            : $package->getPrice(
                 BitcoinUtils::CURRENCY_BTC,
                 [PriceModifierService::PROMOCODE_MODIFIER, PriceModifierService::GROUP_MODIFIER, PriceModifierService::REFERRAL_MODIFIER],
                 ['promocode' => $promocode, 'user' => \Auth::user()]
              );

        if (!$useQiwi) {
            if (!\Auth::user()->haveEnoughBalance($price, BitcoinUtils::CURRENCY_BTC)) {
                return redirect()->back()->with('flash_warning', 'Недостаточно средств на балансе');
            }
        }

        $order = $this->shop->buy(\Auth::user(), $good, $package, $position, $guarantee, $useQiwi,
            $preorderServices, $preorderComment, $promocode);

        return redirect('/orders/' . $order->id, 303)->with('flash_success',
            $useQiwi ? 'Товар успешно зарезервирован!' : 'Покупка успешно совершена!');
    }

    public function showMessageForm(Request $request, $slug)
    {
        $user = Auth::user();

        if(!$user->employee && in_array($user->role, [User::ROLE_USER, User::ROLE_CATALOG]) && ($user->buy_count < 1 && $user->orders->count() < 1)) {
            $shop = Shop::getDefaultShop();
            return redirect('/shop/' . $shop->slug)->with('flash_warning', 'Нужно совершить покупку для того, чтобы писать сообщения магазину.');
        }

        $employees = $this->shop->employees()
            ->where('sections_messages_private', true)
            ->with(['user'])
            ->get();

        return view('shop.message', [
            'receivers' => $employees
        ]);
    }

    public function message(Request $request, $slug)
    {
        $user = Auth::user()->load(['orders']);

        if(!$user->employee && in_array($user->role, [User::ROLE_USER, User::ROLE_CATALOG]) && ($user->buy_count < 1 && $user->orders->count() < 1)) {
            $shop = Shop::getDefaultShop();
            return redirect('/shop/' . $shop->slug)->with('flash_warning', 'Нужно совершить покупку для того, чтобы писать сообщения магазину.');
        }

        $this->validate($request, [
            'title' => 'required|min:2',
            'body' => 'required|min:3|max:3000',
            'receiver' => 'required|in:shop,user,exchange',
            'receiver_id' => 'required|numeric'
        ]);

        $employee = null;
        if ($request->get('receiver') === 'user') {
            /** @var Employee $employee */
            $employee = $this->shop->employees()
                ->where(function($query) {
                    return $query
                        ->where('role', Employee::ROLE_OWNER)
                        ->orWhere('sections_messages_private', true);
                })
                ->findOrFail($request->get('receiver_id'));
            $receiver = $employee->user_id;
        } elseif ($request->get('receiver') === 'shop') {
            $receiver = -$this->shop->id;
        } elseif ($request->get('receiver') === 'exchange') {
            $exchange = $this->shop->getActiveQiwiExchange();
            if (!$exchange) {
                abort(403);
            }
            $receiver = $exchange->user_id;
        }

        if ($receiver === \Auth::user()->id) {
            return redirect()->back(303)->withInput()->with('flash_warning', 'Вы не можете отправить сообщение самому себе!');
        }

        /** @var Thread $thread */
        $thread = Thread::create([
            'subject' => $request->get('title'),
        ]);

        Message::create([
            'thread_id' => $thread->id,
            'user_id' => \Auth::user()->id,
            'body' => $request->get('body'),
            'system' => false
        ]);

        Participant::create([
            'thread_id' => $thread->id,
            'user_id' => \Auth::user()->id,
            'last_read' => new Carbon()
        ]);

        $thread->addParticipant($receiver);

        if ($employee !== NULL &&
            $employee->role !== Employee::ROLE_OWNER && // not sending to owner
            ($owner = $this->shop->owner())->id !== \Auth::id() && // not sending from owner
            $employee->sections_messages_private_autojoin
        ) {
            $thread->addParticipant($owner->id);
            Message::create([
                'thread_id' => $thread->id,
                'user_id' => \Auth::user()->id,
                'body' => 'Владелец магазина автоматически добавлен в диалог.',
                'system' => true
            ]);
        }

        return redirect('/messages/' . $thread->id, 303)->with('flash_success', 'Сообщение создано!');
    }
}
