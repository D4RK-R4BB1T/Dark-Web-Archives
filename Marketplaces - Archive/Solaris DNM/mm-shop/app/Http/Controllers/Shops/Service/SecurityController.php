<?php

namespace App\Http\Controllers\Shops\Service;

use App\Http\Controllers\Shops\ServiceController;
use App\Http\Requests\CatalogSyncToggleRequest;
use App\Http\Requests\ShopToggleOverrideRequest;
use App\MessengerModels\Message;
use App\MessengerModels\Thread;
use App\Packages\Utils\PlanUtils;
use App\Providers\DynamicPropertiesProvider;
use App\Shop;
use App\User;
use App\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SecurityController extends ServiceController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function shopForm()
    {
        $servicePage = 'shop';
        $shop = Shop::getDefaultShop();
        $propertiesProvider = \App::make(DynamicPropertiesProvider::class);
        $propertiesProvider->register($shop->id);

        return view('shop.service.security.shop', compact('shop', 'propertiesProvider', 'servicePage'));
    }

    public function integrationsForm()
    {
        $servicePage = 'integrations';
        $shop = Shop::getDefaultShop();
        $propertiesProvider = \App::make(DynamicPropertiesProvider::class);
        $propertiesProvider->register($shop->id);

        return view('shop.service.security.integrations', compact('shop', 'propertiesProvider', 'servicePage'));
    }

    /**
     * @throws \Exception
     */
    public function shopToggle(ShopToggleOverrideRequest $request)
    {
        $shop = Shop::getDefaultShop();
        $propertiesProvider = \App::make(DynamicPropertiesProvider::class);
        $propertiesProvider->register($shop->id);
        $user_id = \Auth::user()->id;

        // toggle shop enabled flag & change disabled reason
        if(!$request->get('enabled') && !$request->has('enable')) {
            // disable shop
            if(!$request->has('change_reason')) {
                $propertiesProvider->setBool(DynamicPropertiesProvider::KEY_ENABLED, false);

                // create new thread
                $thread = Thread::create([
                    'subject' => 'Магазин закрыт службой безопасности.',
                ]);

                Message::create([
                    'thread_id' => $thread->id,
                    'user_id' => $user_id,
                    'body' => $request->has('reason') ? "Причина закрытия:\n" . $request->get('reason') : 'Причина закрытия не указана.',
                    'system' => 0,
                ]);

                $thread->addParticipant(-$shop->id);
                $thread->addParticipant($user_id);
            }

            // set / update reason
            $shop->disabled_reason = $request->get('reason') ? $request->get('reason') : null;
        } else {
            // enable shop
            $shop->disabled_reason = null;
            $propertiesProvider->delete(DynamicPropertiesProvider::KEY_ENABLED);
        }

        $shop->save();

        return redirect('/shop/service/security/shop')
            ->with($shop->enabled ? 'flash_success' : 'flash_warning',
                   'Магазин ' . ($shop->enabled ? 'включен.' : 'выключен. Только владелец и СБ смогут залогиниться.')
            );
    }

    public function integrationsToggle(CatalogSyncToggleRequest $request)
    {
        $shop = Shop::getDefaultShop();
        $propertiesProvider = \App::make(DynamicPropertiesProvider::class);
        $propertiesProvider->register($shop->id);

        // disable synchronization
        if(!$request->get('enabled')) {
            $propertiesProvider->setBool(DynamicPropertiesProvider::KEY_INTEGRATION_CATALOG, false);
        } else {
            $propertiesProvider->delete(DynamicPropertiesProvider::KEY_INTEGRATION_CATALOG);
        }

        return redirect('/shop/service/security/integrations')
            ->with($shop->enabled ? 'flash_success' : 'flash_warning',
                   'Синхронизация с каталогом ' . ($shop->enabled ? 'включена.' : 'выключена. Только работники СБ смогут поменять значение.')
            );
    }

    public function showPlanForm()
    {
        $servicePage = 'plan';
        $shop = Shop::getDefaultShop();

        $plans = collect([Shop::PLAN_BASIC, Shop::PLAN_ADVANCED, Shop::PLAN_INDIVIDUAL_FEE])->map(function ($plan) use ($shop) {
            return [
                'value' => $plan,
                'name' => PlanUtils::getHumanPlanName($plan),
                'description' => PlanUtils::getPlanDescription($plan),
                'selected' => $shop->plan == $plan
            ];
        });
        return view('shop.service.security.plan', compact('shop', 'plans', 'servicePage'));
    }

    public function plan(Request $request)
    {
        $this->validate($request, [
            'expires_at' => 'required|date',
            'plan' => 'required|in:' . implode(',', [Shop::PLAN_BASIC, Shop::PLAN_ADVANCED, Shop::PLAN_INDIVIDUAL_FEE])
        ]);

        $shop = Shop::getDefaultShop();
        $shop->plan = $request->get('plan');
        $shop->expires_at = Carbon::createFromTimestamp(strtotime($request->get('expires_at')));
        $shop->save();

        return redirect()->back()->with('flash_success', 'Настройки сохранены.');
    }

    public function showUsersForm(Request $request)
    {
        $servicePage = 'users';
        $shop = Shop::getDefaultShop();
        $users = User::with(['wallets'])
            ->applySearchFilters($request)
            ->paginate(20);
        $shopWallets = Wallet::where('shop_id', '=', $shop->id)
            ->get();

        return view('shop.service.security.users.index',
            compact('users', 'shop', 'servicePage', 'shopWallets'));
    }
}
