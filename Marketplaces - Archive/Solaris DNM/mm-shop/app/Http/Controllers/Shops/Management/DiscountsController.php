<?php


namespace App\Http\Controllers\Shops\Management;



use App\Packages\Utils\BitcoinUtils;
use App\Promocode;
use App\User;
use App\UserGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\Rule;

class DiscountsController extends ManagementController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function($request, $next) {
            $this->authorize('management-sections-discounts');
            return $next($request);
        });

        \View::share('page', 'discounts');
    }

    public function index(Request $request)
    {
        return redirect('/shop/management/discounts/promo');
    }

    public function promo(Request $request)
    {
        \View::share('section', 'promo');

        $codes = Promocode::with(['employee.user'])
            ->orderBy('id', 'desc')
            ->paginate(30);
        
        return view('shop.management.discounts.promo.index', ['codes' => $codes]);
    }

    public function showPromoAddForm(Request $request)
    {
        return view('shop.management.discounts.promo.add');
    }

    public function promoAdd(Request $request)
    {
        $this->validate($request, [
            'discount_mode' => [
                'required',
                Rule::in([Promocode::DISCOUNT_MODE_PRICE, Promocode::DISCOUNT_MODE_PERCENT])
            ],
            'price_amount' => [
                'required_if:discount_mode,' . Promocode::DISCOUNT_MODE_PRICE,
                'numeric',
                'min:' . ($request->get('currency') == BitcoinUtils::CURRENCY_BTC ? 0.0001 : 10)
            ],
            'price_currency' => [
                'required_if:discount_mode,' . Promocode::DISCOUNT_MODE_PRICE,
                Rule::in([BitcoinUtils::CURRENCY_BTC, BitcoinUtils::CURRENCY_RUB, BitcoinUtils::CURRENCY_USD])
            ],
            'percent_amount' => [
                'required_if:discount_mode,' . Promocode::DISCOUNT_MODE_PERCENT,
                'numeric',
                'min:1',
                'max:100'
            ],
            'mode' => [
                'required',
                Rule::in([Promocode::MODE_SINGLE_USE, Promocode::MODE_UNTIL_DATE])
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after:today'
            ]
        ]);

        Promocode::create([
            'employee_id' => \Auth::user()->employee->id,
            'code' => Promocode::generate(),
            'discount_mode' => $request->get('discount_mode'),
            'percent_amount' =>
                $request->get('discount_mode') == Promocode::DISCOUNT_MODE_PERCENT
                    ? $request->get('percent_amount')
                    : null,
            'price_amount' =>
                $request->get('discount_mode') == Promocode::DISCOUNT_MODE_PRICE
                    ? $request->get('price_amount')
                    : null,
            'price_currency' =>
                $request->get('discount_mode') == Promocode::DISCOUNT_MODE_PRICE
                    ? $request->get('price_currency')
                    : null,
            'mode' => $request->get('mode'),
            'expires_at' => $request->has('expires_at') ? Carbon::createFromTimestampUTC(strtotime($request->get('expires_at'))) : null,
        ]);

        return redirect('/shop/management/discounts/promo')->with('flash_success', 'Промокод добавлен!');
    }

    public function showPromoEditForm(Request $request, $promocodeId)
    {
        $promocode = Promocode::findOrFail($promocodeId);
        return view('shop.management.discounts.promo.edit', ['promocode' => $promocode]);
    }

    public function promoEdit(Request $request, $promocodeId)
    {
        $this->validate($request, [
            'discount_mode' => [
                'required',
                Rule::in([Promocode::DISCOUNT_MODE_PRICE, Promocode::DISCOUNT_MODE_PERCENT])
            ],
            'price_amount' => [
                'required_if:discount_mode,' . Promocode::DISCOUNT_MODE_PRICE,
                'numeric',
                'min:' . ($request->get('currency') == BitcoinUtils::CURRENCY_BTC ? 0.0001 : 10)
            ],
            'price_currency' => [
                'required_if:discount_mode,' . Promocode::DISCOUNT_MODE_PRICE,
                Rule::in([BitcoinUtils::CURRENCY_BTC, BitcoinUtils::CURRENCY_RUB, BitcoinUtils::CURRENCY_USD])
            ],
            'percent_amount' => [
                'required_if:discount_mode,' . Promocode::DISCOUNT_MODE_PERCENT,
                'numeric',
                'min:1',
                'max:100'
            ],
            'mode' => [
                'required',
                Rule::in([Promocode::MODE_SINGLE_USE, Promocode::MODE_UNTIL_DATE])
            ],
            'expires_at' => [
                'nullable',
                'date',
            ]
        ]);

        $promocode = Promocode::findOrFail($promocodeId);
        $promocode->update([
            'discount_mode' => $request->get('discount_mode'),
            'percent_amount' =>
                $request->get('discount_mode') == Promocode::DISCOUNT_MODE_PERCENT
                    ? $request->get('percent_amount')
                    : null,
            'price_amount' =>
                $request->get('discount_mode') == Promocode::DISCOUNT_MODE_PRICE
                    ? $request->get('price_amount')
                    : null,
            'price_currency' =>
                $request->get('discount_mode') == Promocode::DISCOUNT_MODE_PRICE
                    ? $request->get('price_currency')
                    : null,
            'mode' => $request->get('mode'),
            'expires_at' => $request->has('expires_at') ? Carbon::createFromTimestampUTC(strtotime($request->get('expires_at'))) : null,
            'is_active' => $request->has('is_active')
        ]);

        return redirect('/shop/management/discounts/promo')->with('flash_success', 'Промокод отредактирован!');
    }

    public function groups(Request $request)
    {
        \View::share('section', 'groups');

        $groups = UserGroup::withCount('users')->paginate(30);

        return view('shop.management.discounts.groups.index', ['groups' => $groups]);
    }

    public function showGroupsAddForm(Request $request)
    {
        return view('shop.management.discounts.groups.add');
    }

    public function groupsAdd(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|min:1',
            'percent_amount' => 'required|numeric|max:100',
            'mode' => 'required|in:' . implode(',', [UserGroup::MODE_MANUAL, UserGroup::MODE_AUTO]),
            'buy_count' => 'required_if:mode,' . UserGroup::MODE_AUTO
        ]);

        $buyCount = $request->get('buy_count', null);
        if ($request->get('mode') == UserGroup::MODE_AUTO && $buyCount && UserGroup::whereBuyCount($buyCount)->exists()) {
            return redirect()->back()->withInput()->with('flash_warning', 'Группа с таким же количеством покупок для перехода уже существует');
        }
        UserGroup::create([
            'title' => $request->get('title'),
            'percent_amount' => $request->get('percent_amount'),
            'mode' => $request->get('mode'),
            'buy_count' => ($request->get('mode') == UserGroup::MODE_AUTO)
                ? $request->get('buy_count')
                : null
        ]);

        return redirect('/shop/management/discounts/groups')->with('flash_success', 'Группа создана');
    }

    public function showGroupsEditForm(Request $request, $groupId)
    {
        $group = UserGroup::findOrFail($groupId);
        return view('shop.management.discounts.groups.edit', ['group' => $group]);
    }

    public function groupsEdit(Request $request, $groupId)
    {
        /** @var UserGroup $group */
        $group = UserGroup::findOrFail($groupId);
        $this->validate($request, [
            'title' => 'required|min:1',
            'percent_amount' => 'required|numeric|max:100',
            'mode' => 'required|in:' . implode(',', [UserGroup::MODE_MANUAL, UserGroup::MODE_AUTO]),
            'buy_count' => 'required_if:mode,' . UserGroup::MODE_AUTO
        ]);

        $buyCount = $request->get('buy_count', null);
        if ($request->get('mode') == UserGroup::MODE_AUTO && $buyCount && UserGroup::where('id', '!=', $group->id)->whereBuyCount($buyCount)->exists()) {
            return redirect()->back()->withInput()->with('flash_warning', 'Группа с таким же количеством покупок для перехода уже существует');
        }

        $group->title = $request->get('title');
        $group->percent_amount = $request->get('percent_amount');
        $group->mode = $request->get('mode');
        $group->buy_count = ($request->get('mode') == UserGroup::MODE_AUTO)
            ? $request->get('buy_count')
            : null;
        $group->save();
        return redirect('/shop/management/discounts/groups')->with('flash_success', 'Группа отредактирована');
    }

    public function showGroupsDeleteForm(Request $request, $groupId)
    {
        /** @var UserGroup $group */
        $group = UserGroup::findOrFail($groupId);
        return view('shop.management.discounts.groups.delete', ['group' => $group]);
    }

    public function groupsDelete(Request $request, $groupId)
    {
        /** @var UserGroup $group */
        $group = UserGroup::findOrFail($groupId);
        User::whereGroupId($group->id)->update(['group_id' => null]);
        $group->delete();
        return redirect('/shop/management/discounts/groups')->with('flash_success', 'Группа удалена');
    }

    public function showGroupsManualForm(Request $request, $groupId)
    {
        /** @var UserGroup $group */
        $group = UserGroup::findOrFail($groupId);
        abort_if($group->mode != UserGroup::MODE_MANUAL, 404);

        $users = $group->users()->paginate(30);
        return view('shop.management.discounts.groups.manual', ['users' => $users, 'group' => $group]);
    }

    public function groupsManual(Request $request, $groupId)
    {
        /** @var UserGroup $group */
        $group = UserGroup::findOrFail($groupId);
        abort_if($group->mode != UserGroup::MODE_MANUAL, 404);

        $this->validate($request, [
            'username' => 'required|exists:users,username'
        ]);

        $username = trim($request->get('username'));
        User::whereUsername($username)->update([
            'group_id' => $group->id
        ]);
        return redirect()->back()->with('flash_success', 'Пользователь добавлен.');
    }

    public function groupsManualDelete(Request $request, $groupId, $userId)
    {
        if (\Session::get('_token') !== $request->get('_token')) {
            throw new TokenMismatchException;
        }

        /** @var UserGroup $group */
        $group = UserGroup::findOrFail($groupId);
        abort_if($group->mode != UserGroup::MODE_MANUAL, 404);

        /** @var User $user */
        $user = $group->users()->findOrFail($userId);
        $user->group_id = null;
        $user->save();

        return redirect()->back()->with('flash_success', 'Пользователь удален из группы. Воспользуйтесь Мастером назначения групп для установки группы по кол-ву покупок.');
    }

    public function showGroupsMasterForm(Request $request)
    {
        $users = User::with(['group'])
            ->orderBy('buy_count', 'desc')
            ->get()
            ->filter(function ($user) {
                return !is_null($user->suggestDiscountGroup());
            });

        return view('shop.management.discounts.groups.master', [
            'users' => $users
        ]);
    }

    public function groupsMaster(Request $request)
    {
        $users = User::with(['group'])
            ->orderBy('buy_count', 'desc')
            ->get()
            ->filter(function ($user) {
                return !is_null($user->suggestDiscountGroup());
            });

        foreach ($users as $user) {
            $user->group_id = $user->suggestDiscountGroup()->id;
            $user->save();
        }

        return redirect('/shop/management/discounts/groups')->with('flash_success', 'Пользователи распределены.');
    }
}