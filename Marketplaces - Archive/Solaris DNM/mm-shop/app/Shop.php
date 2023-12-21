<?php

namespace App;

use App\Events\PositionPurchased;
use App\Jobs\CreateBitcoinWallet;
use App\MessengerModels\Thread;
use App\Packages\PriceModifier\PriceModifierService;
use App\Packages\Utils\BitcoinUtils;
use App\Packages\Utils\Formatters;
use App\Packages\Utils\PlanUtils;
use App\Traits\Walletable;
use Carbon\Carbon;
use Cmgmyr\Messenger\Models\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Shop
 *
 * @property integer $id
 * @property string $slug
 * @property string $title
 * @property string $image_url
 * @property boolean $enabled
 * @property string $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Employee[] $employees
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereSlug($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereImageUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereEnabled($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereExpiresAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Good[] $goods
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\PaidService[] $services
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\EmployeesLog[] $employeesLog
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MessengerModels\Thread[] $threads
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MessengerModels\Message[] $messages
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MessengerModels\Participant[] $participants
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Wallet[] $wallets
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Page[] $pages
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\QiwiWallet[] $qiwiWallets
 * @property string $banner_url
 * @property string $information
 * @property string $problem
 * @property integer $employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\EmployeesEarning[] $employeesEarnings
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\EmployeesPayout[] $employeesPayouts
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereBannerUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereInformation($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereProblem($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereEmployeesCount($value)
 * @property string $plan
 * @property integer $qiwi_count
 * @method static \Illuminate\Database\Query\Builder|\App\Shop wherePlan($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereQiwiCount($value)
 * @property boolean $search_enabled
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereSearchEnabled($value)
 * @property string $visitors_chart_url
 * @property string $orders_chart_url
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereVisitorsChartUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereOrdersChartUrl($value)
 * @property bool $guest_enabled
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereGuestEnabled($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\AccountingLot[] $lots
 * @property bool $categories_enabled
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereCategoriesEnabled($value)
 * @property bool $integrations_eos
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereIntegrationsEos($value)
 * @property bool $integrations_catalog
 * @property int $buy_count
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereBuyCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereIntegrationsCatalog($value)
 * @property string $previous_plan
 * @method static \Illuminate\Database\Query\Builder|\App\Shop wherePreviousPlan($value)
 * @property bool $integrations_telegram
 * @property string $integrations_telegram_news
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereIntegrationsTelegram($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereIntegrationsTelegramNews($value)
 * @property bool $integrations_qiwi_api
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereIntegrationsQiwiApi($value)
 * @property string $integrations_qiwi_api_url
 * @property string $integrations_qiwi_api_key
 * @property string $integrations_qiwi_api_last_response
 * @property string $integrations_qiwi_api_last_sync_at
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereIntegrationsQiwiApiKey($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereIntegrationsQiwiApiLastResponse($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereIntegrationsQiwiApiLastSyncAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereIntegrationsQiwiApiUrl($value)
 * @property string $integrations_qiwi_exchange_invite
 * @property int $integrations_qiwi_exchange_id
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereIntegrationsQiwiExchangeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereIntegrationsQiwiExchangeInvite($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\QiwiExchange[] $qiwiExchanges
 * @property bool $tor2web_protect_enabled
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereTor2webProtectEnabled($value)
 * @property bool $referral_enabled
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereReferralEnabled($value)
 * @property bool $integrations_quests_map
 * @property bool $withdraw_shop_wallet
 * @property string $disabled_reason
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereDisabledReason($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereIntegrationsQuestsMap($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Shop whereWithdrawShopWallet($value)
 */
class Shop extends Model
{
    use \App\MessengerModels\Traits\Messageable, Walletable;

    const PLAN_BASIC = 'basic';
    const PLAN_ADVANCED = 'advanced';
    const PLAN_INDIVIDUAL = 'individual';
    const PLAN_FEE = 'fee';
    const PLAN_INDIVIDUAL_FEE = 'individual_fee';

    protected $table = 'shops';
    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $casts = [
        'expires_at' => 'datetime',
        'integrations_qiwi_api_last_sync_at' => 'datetime'
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'slug', 'title', 'image_url', 'banner_url',
        'information', 'problem', 'search_enabled', 'categories_enabled', 'guest_enabled', 'referral_enabled',
        'integrations_catalog', 'integrations_eos', 'integrations_telegram', 'integrations_telegram_news',
        'integrations_qiwi_api', 'integrations_qiwi_api_url', 'integrations_qiwi_api_key', 'integrations_qiwi_api_last_response', 'integrations_qiwi_api_last_sync_at',
//        'integrations_qiwi_exchange_id',
        'integrations_quests_map',
        'enabled',
        'withdraw_shop_wallet', 'disabled_reason',
        'expires_at',
    ];

    /**
     * @return Shop
     */
    private static $_shop = null;
    public static function getDefaultShop()
    {
        if (self::$_shop === null) {
            try {
                self::$_shop = Shop::find(1);
            } catch (\PDOException $e) { // shop is not initialized yet
                return null;
            }
        }
        return self::$_shop;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|Employee[]
     */
    public function employees()
    {
        return $this->hasMany('App\Employee', 'shop_id', 'id');
    }

    public function isExpired()
    {
        return $this->expires_at->startOfDay() <= Carbon::now();
    }

    /**
     * @return User
     */
    public function owner()
    {
        // select * from `employees` where `employees`.`shop_id` = ? and `role` = 'owner' limit 1
        $employee = $this->employees()->where('role', Employee::ROLE_OWNER)->first();
        // select * from `users` where `users`.`id` = ? limit 1
        return $employee->user;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|Good[]
     */
    public function goods()
    {
        return $this->hasMany('App\Good', 'shop_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|Order[]
     */
    public function orders()
    {
        return $this->hasMany('App\Order', 'shop_id', 'id');
    }

    public function preordersCount()
    {
        return $this->orders()->where('status', Order::STATUS_PREORDER_PAID)->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|Good[]
     */
    public function availableGoods()
    {
        return $this->goods()->whereHas('packages', function ($packages) {
            $packages->where('has_quests', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|PaidService[]
     */
    public function services()
    {
        return $this->hasMany('App\PaidService', 'shop_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|EmployeesLog[]
     */
    public function employeesLog()
    {
        return $this->hasMany('App\EmployeesLog', 'shop_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|QiwiWallet[]
     */
    public function qiwiWallets()
    {
        return $this->hasMany('App\QiwiWallet', 'shop_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|QiwiExchange[]
     */
    public function qiwiExchanges()
    {
        return $this->hasMany('App\QiwiExchange', 'shop_id', 'id');
    }

    /**
     * Thread relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function threads()
    {
        $this->id = -$this->id;
        $result = $this->belongsToMany(
            Models::classname(Thread::class),
            Models::table('participants'),
            'user_id',
            'thread_id'
        );
        $this->id = -$this->id;

        return $result;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|Wallet[]
     */
    public function wallets()
    {
        return $this->hasMany('App\Wallet', 'shop_id', 'id');
    }

    /**
     * @return Wallet|null
     */
    public function primaryWallet()
    {
        return $this->wallets->filter(function($item) {
            return $item->type == Wallet::TYPE_PRIMARY;
        })->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Wallet[]
     */
    public function additionalWallets()
    {
        return $this->wallets->filter(function($item) {
            return $item->type == Wallet::TYPE_ADDITIONAL;
        });
    }

    /**
     * @return Operation|Operation[]|Builder
     */
    public function operations()
    {
        return Operation::whereIn('wallet_id', $this->wallets->pluck('id'));
    }

    /**
     * @return Operation|Operation[]|Builder
     */
    public function trashedOperations()
    {
        return Operation::whereIn('wallet_id', $this->wallets()->withTrashed()->pluck('id'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|Page[]
     */
    public function pages()
    {
        return $this->hasMany('App\Page', 'shop_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|EmployeesEarning[]
     */
    public function employeesEarnings()
    {
        return $this->hasMany('App\EmployeesEarning', 'shop_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|EmployeesPayout[]
     */
    public function employeesPayouts()
    {
        return $this->hasMany('App\EmployeesPayout', 'shop_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Builder|AccountingLot[]
     */
    public function lots()
    {
        return $this->hasMany('App\AccountingLot', 'shop_id', 'id');
    }

    /**
     * @param User $user
     * @return Shop
     */
    public static function init($user) 
    {
        $user->role = User::ROLE_SHOP;
        $user->save();

        /** @var Shop $shop */
        $shop = Shop::create([
            'slug' => \Hashids::encode($user->id),
            'title' => '(name not set)',
            'expires_at' => Carbon::now()->addMonth(),
            'enabled' => false
        ]);

        //$shop->integrations_qiwi_exchange_invite = \Illuminate\Support\Str::random();
        $shop->save();

        Employee::create([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'description' => 'Владелец магазина',
            'role' => Employee::ROLE_OWNER,
        ]);

        $job = new CreateBitcoinWallet($shop, Wallet::TYPE_PRIMARY, ['title' => 'Основной кошелек']);
        dispatch($job);

        return $shop;
    }

    public function buy(User $user, Good $good, GoodsPackage $package, GoodsPosition $position = null,
                        $guarantee = false,
                        $useQiwi = false,
                        $preorderServices = [], $preorderComment = null,
                        $promocode = null) {

        $order = \DB::transaction(function () use ($user, $good, $package, $position, $guarantee, $preorderServices, $useQiwi, $preorderComment, $promocode) {
            /** @var GoodsPosition $position */
            /** @var Promocode $promocode */

            if ($useQiwi) {
                assert($package->qiwi_enabled);
                /** @var QiwiWallet $qiwiWallet */
                $qiwiWallet = $this->qiwiWallets()->availableForPackage($package)->lockForUpdate()->firstOrFail();
            }

            if (!$package->preorder) {
                assert($position !== null);
                assert($position->available == true);

                $position->update([
                    'available' => false
                ]);
            }

            $userPrice = $guarantee
                ? $package->getPriceWithGuaranteeFee(BitcoinUtils::CURRENCY_BTC)
                : $package->getPrice(
                      BitcoinUtils::CURRENCY_BTC,
                      [PriceModifierService::PROMOCODE_MODIFIER, PriceModifierService::GROUP_MODIFIER, PriceModifierService::REFERRAL_MODIFIER],
                      ['promocode' => $promocode, 'user' => $user]
                  );

            if ($useQiwi) {
                $userPrice = $package->getQiwiPrice(
                    BitcoinUtils::CURRENCY_BTC,
                    [PriceModifierService::PROMOCODE_MODIFIER, PriceModifierService::GROUP_MODIFIER, PriceModifierService::REFERRAL_MODIFIER],
                    ['promocode' => $promocode, 'user' => $user]
                );
            }

            foreach ($preorderServices as $service) {
                $userPrice += $service->getPrice(BitcoinUtils::CURRENCY_BTC);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'city_id' => $package->city_id,
                'shop_id' => $this->id,
                'promocode_id' => ($promocode) ? $promocode->id : null,
                'position_id' => ($package->preorder) ? null : $position->id,
                'good_id' => $good->id,
                'good_title' => $good->title,
                'good_image_url' => $good->image_url,
                'package_id' => $package->id,
                'package_amount' => $package->amount,
                'package_measure' => $package->measure,
                'package_price' => $package->price,
                'package_currency' => $package->currency,
                'package_price_btc' => $package->getPrice(BitcoinUtils::CURRENCY_BTC),
                'package_preorder' => $package->preorder,
                'package_preorder_time' => $package->preorder_time,
                'comment' => ($package->preorder) ? $preorderComment : null,
                'status' => ($package->preorder)
                    ? ($useQiwi ? Order::STATUS_QIWI_RESERVED : Order::STATUS_PREORDER_PAID)
                    : ($useQiwi ? Order::STATUS_QIWI_RESERVED : Order::STATUS_PAID),
                'status_was_problem' => false,
                'guarantee' => $guarantee,
                'referrer_fee' => $user->referral_fee,
                'user_price_btc' => $userPrice,
                'group_percent_amount' => $user->group ? $user->group->percent_amount : null,
                'group_title' => $user->group ? $user->group->title : null
            ]);


            foreach ($preorderServices as $service) {
                OrdersService::create([
                    'order_id' => $order->id,
                    'title' => $service->title,
                    'price' => $service->price,
                    'currency' => $service->currency,
                    'price_btc' => $service->getPrice(BitcoinUtils::CURRENCY_BTC)
                ]);
            }

            if ($useQiwi) { // do not need to perform balance operations
                $shopIdInt = base_convert(config('mm2.application_id'), 32, 10) % 100000; // unique integer salt for shop
                $orderIdInt = ((3663002302 + $shopIdInt) * $order->id) % 1000000; // 3663002302 is a prime number, used only for id obfuscation

                $userPrice = btc2rub($userPrice) * 1.00; // TODO QIWI fee
                QiwiTransaction::create([
                    'qiwi_wallet_id' => $qiwiWallet->id,
                    'order_id' => $order->id,
                    'amount' => $userPrice,
                    'status' => QiwiTransaction::STATUS_RESERVED,
                    'comment' => $shopIdInt . '-' . $orderIdInt
                ]);

                $qiwiWallet->reserved_balance += $userPrice;
                $qiwiWallet->save();
            } else {
                assert($user->haveEnoughBalance($userPrice, BitcoinUtils::CURRENCY_BTC));

                $user->balanceOperation(-$userPrice, BitcoinUtils::CURRENCY_BTC, 'Оплата заказа', ['order_id' => $order->id]);
                if (!$package->preorder) { // payments for preorders is credited after adding an address, see Shops\Management\OrdersController@order
                    $productPrice = $package->getPrice(
                        BitcoinUtils::CURRENCY_BTC,
                        [PriceModifierService::PROMOCODE_MODIFIER, PriceModifierService::GROUP_MODIFIER],
                        ['promocode' => $promocode, 'user' => $user]
                    );
                    $referrerFee = $package->getPrice(
                        BitcoinUtils::CURRENCY_BTC,
                        [PriceModifierService::PROMOCODE_MODIFIER, PriceModifierService::GROUP_MODIFIER, PriceModifierService::REFERRAL_MODIFIER],
                        ['promocode' => $promocode, 'user' => $user]
                    ) - $productPrice;

                    if ($promocode) {
                        $promocode->markUsedIfNeeded();
                    }
                    $this->balanceOperation($productPrice, BitcoinUtils::CURRENCY_BTC, 'Продажа товара', ['order_id' => $order->id]);
                    if (($referrer = $user->referrer) && $referrerFee > 0) {
                        $referrer->balanceOperation($referrerFee, BitcoinUtils::CURRENCY_BTC, 'Процент с покупки товара рефералом');
                    }
                    $fee = PlanUtils::getFeeForOrder($this->plan, $productPrice);
                    if ($fee && $fee > 0) {
                        $this->balanceOperation(-$fee, BitcoinUtils::CURRENCY_BTC, 'Комиссия за продажу', ['order_id' => $order->id]);
                        Income::create([
                            'wallet_id' => $this->primaryWallet()->id,
                            'amount_usd' => btc2usd($fee),
                            'amount_btc' => $fee,
                            'description' => 'Комиссия за продажу ' . $order->id
                        ]);
                    }
                } else {
                    if ($promocode) {
                        $promocode->markUsedIfNeeded();
                    }
                }
            }

            if (!$package->preorder) {
                event(new PositionPurchased($position, $user));
            }

            return $order;
        });

        return $order;
    }

    /**
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function avatar()
    {
        if ($this->image_url) {
            return url($this->image_url);
        } else {
            return url('/assets/img/no-avatar.gif');
        }
    }

    /**
     * @return string
     */
    public function getPublicName()
    {
        return e($this->title);
    }

    /**
     * @return string
     */
    public function getPublicDecoratedName()
    {
        return "<b class=\"text-info\">".e($this->title)."</b>";
    }

    /**
     * @return string
     */
    public function getHumanPlanName()
    {
        return PlanUtils::getHumanPlanName($this->plan);
    }

    /**
     * @param string $currency
     * @return float
     */
    public function getPlanPrice($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return PlanUtils::getPlanPrice($this->plan, $currency);
    }

    /**
     * @param string $currency
     * @return string
     */
    public function getHumanPlanPrice($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return human_price($this->getPlanPrice($currency), $currency);
    }

    /**
     * @param string $currency
     * @return float
     */
    public function getTotalPlanPrice($currency = BitcoinUtils::CURRENCY_BTC)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return '-';
        }

        return $this->getPlanPrice($currency) +
            ($this->getAdditionalEmployeePrice($currency) * $this->employees_count) +
            ($this->getAdditionalQiwiWalletPrice($currency) * $this->qiwi_count);
    }

    /**
     * @param string $currency
     * @return string
     */
    public function getHumanTotalPlanPrice($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return human_price($this->getTotalPlanPrice($currency), $currency);
    }

    /**
     * @return integer
     */
    public function getPlanAvailableEmployeesCount()
    {
        return PlanUtils::getPlanAvailableEmployeesCount($this->plan);
    }
    /**
     * @return integer
     */
    public function getTotalAvailableEmployeesCount()
    {
        return $this->getPlanAvailableEmployeesCount() + $this->employees_count;
    }

    /**
     * @param string $currency
     * @return float
     */
    public function getAdditionalEmployeePrice($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return PlanUtils::getAdditionalEmployeePrice($this->plan, $currency);
    }

    /**
     * @param string $currency
     * @return string
     */
    public function getHumanAdditionalEmployeePrice($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return human_price($this->getAdditionalEmployeePrice($currency), $currency);
    }

    /**
     * @return integer
     */
    public function getPlanAvailableQiwiWalletsCount()
    {
        return PlanUtils::getPlanAvailableQiwiWalletsCount($this->plan);
    }

    /**
     * @return integer
     */
    public function getTotalAvailableQiwiWalletsCount()
    {
        return $this->getPlanAvailableQiwiWalletsCount() + $this->qiwi_count;
    }

    /**
     * @param string $currency
     * @return float
     */
    public function getAdditionalQiwiWalletPrice($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return PlanUtils::getAdditionalQiwiWalletPrice($this->plan, $currency);
    }

    /**
     * @param string $currency
     * @return string
     */
    public function getHumanAdditionalQiwiWalletPrice($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return human_price($this->getAdditionalQiwiWalletPrice($currency), $currency);
    }

    /**
     * @return string
     */
    public function getPlanDescription()
    {
        return PlanUtils::getPlanDescription($this->plan);
    }

    public function getRating()
    {
        $ratings = GoodsReview::
            select(\DB::raw('count(*) as c, sum(shop_rating) as sr, sum(dropman_rating) as dr, sum(item_rating) as ir'))
            ->first()
            ->toArray();

        if ($ratings['c'] == 0) {
            return '0.00';
        }

        return sprintf('%.2f',
            ((int) $ratings['sr'] + (int) $ratings['dr'] + (int) $ratings['ir']) / (3 * (int) $ratings['c']));
    }

    public function isCatalogSyncEnabled()
    {
        return true;
    }

    public function isQiwiApiEnabled()
    {
        return (bool) $this->integrations_qiwi_api;
    }

    /**
     * @return QiwiExchange|null
     */
    public function getActiveQiwiExchange()
    {
        return $this->qiwiExchanges()->where('id', $this->integrations_qiwi_exchange_id)->first();
    }
}
