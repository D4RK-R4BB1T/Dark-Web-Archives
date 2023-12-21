<?php

namespace App\Http\Controllers\Shops;

use App\Employee;
use App\Http\Requests\FinancesToggleRequest;
use App\Http\Requests\SecurityServiceAddThreadRequest;
use App\MessengerModels\Message;
use App\MessengerModels\Participant;
use App\MessengerModels\Thread;
use App\Order;
use App\Providers\DynamicPropertiesProvider;
use App\Role;
use App\Shop;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->middleware(function ($request, $next) {
            if (Auth::check()) {
                $role = $request->user()->role()->first();

                switch ($role->id) {
                    case Role::SecurityService:
                    case Role::Admin:
                        $this->service = 'security';
                        break;

                    case Role::JuniorModerator:
                    case Role::SeniorModerator:
                        $this->service = 'moderate';
                        break;
                }

                if($this->service) {
                    \View::share('service', $this->service);
                    \View::share('role', $role);
                }
            }

            return $next($request);
        });
    }

    public function orders(Request $request)
    {
        $servicePage = 'orders';
        $shop = Shop::getDefaultShop();

        $orders = Order::applySearchFilters($request)
            ->with(['city', 'good', 'review', 'position', 'user',
                'user.employee:id' // я не знаю как пофиксить запрос так, чтобы в нем не участвовал EncryptableTrait :( надеюсь это временно
            ])
            ->orderByDesc('id')
            ->paginate(20);
        $goods = $orders->pluck('good')->unique()->flatten();
        $cities = $orders->pluck('city')->unique()->flatten();

        return view('shop.service.orders', compact('shop', 'orders', 'goods', 'cities',
            'servicePage'));
    }

    public function finances()
    {
        $servicePage = 'finances';
        $shop = Shop::getDefaultShop();
        $propertiesProvider = \App::make(DynamicPropertiesProvider::class);
        $propertiesProvider->register($shop->id);

        return view('shop.service.finances', compact(
            'shop',
            'propertiesProvider',
            'servicePage'
        ));
    }

    public function financesToggle(FinancesToggleRequest $request)
    {
        $shop = Shop::getDefaultShop();
        $propertiesProvider = \App::make(DynamicPropertiesProvider::class);
        $propertiesProvider->register($shop->id);

        // disable finances send
        if(!$request->get('enabled')) {
            $propertiesProvider->setBool(DynamicPropertiesProvider::KEY_WDRAW_SHOP_WALLET, false);
        } else {
            $propertiesProvider->delete(DynamicPropertiesProvider::KEY_WDRAW_SHOP_WALLET);
        }

        return redirect('/shop/service/finances')
            ->with($request->get('enabled') ? 'flash_success' : 'flash_warning',
                   'Отправка BTC с кошелька магазина ' . ($request->get('enabled') ? 'включена.' : 'выключена.')
            );
    }

    public function showNewThreadForm()
    {
        $user = Auth::user();
        $shop = Shop::getDefaultShop();
        $threads = Thread::forUser($user->id)
            ->latest('updated_at')->latest('id')
            ->with([
                'participants',
                'latestMessage', 'latestMessage.user'
            ])->paginate(15);
        $employees = Shop::getDefaultShop()->employees()
            ->where('sections_messages_private', true)
            ->with(['user'])
            ->get();

        return view('shop.service.thread.new',
            compact('user', 'threads', 'employees', 'shop'));
    }

    public function newThread(SecurityServiceAddThreadRequest $request)
    {
        $receiver = null;
        $user = Auth::user();
        $shop = Shop::getDefaultShop();

        switch($request->get('receiver')) {
            case 'user':
                $employee = $shop->employees()->where(function($query) {
                    return $query->where('role', Employee::ROLE_OWNER)->orWhere('sections_messages_private', true);
                })->findOrFail($request->get('receiver_id'));
                $receiver = $employee->user_id;
                break;

            case 'shop':
                $receiver = -$shop->id;
                break;

            default:
                abort(404);
        }

        if (!$receiver || $user->id === $receiver) {
            return redirect('/shop/service/messages/new')->withInput()->with('flash_warning', 'Адресат недоступен.');
        }

        $thread = Thread::create(['subject' => $request->get('title')]);

        Message::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'body' => $request->get('message'),
            'system' => false
        ]);

        Participant::create(['thread_id' => $thread->id, 'user_id' => $user->id, 'last_read' => new Carbon()]);
        $thread->addParticipant($receiver);
        return redirect( '/messages/' . $thread->id, 303)->with('flash_success', 'Сообщение отправлено');
    }
}
