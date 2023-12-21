<?php
/**
 * File: APIController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers\API;


use App\Category;
use App\City;
use App\Events\PositionCreated;
use App\Good;
use App\GoodsPackage;
use App\GoodsPosition;
use App\Http\Controllers\Controller;
use App\Income;
use App\Order;
use App\Packages\NicknameGenerator;
use App\Packages\PriceModifier\ReferralPriceModifier;
use App\Packages\Utils\BitcoinUtils;
use App\Packages\Utils\Formatters;
use App\Packages\Utils\PlanUtils;
use App\QiwiExchangeRequest;
use App\QiwiTransaction;
use App\QiwiWallet;
use App\Region;
use App\Shop;
use App\User;
use App\Wallet;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Nbobtc\Command\Command;

class APIController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function($request, $next) {
            $apiKey = $request->get('api_key', '');
            if ($apiKey !== config('mm2.application_api_key')) {
                return response()->json([
                    'error' => [
                        'error_code' => 403,
                        'error_description' => 'Unauthorized.',
                        '* secret_message *' => 'Guys, do not brute-force the directories. It\'s not that. This is an another thing. Better not to get into that. Seriously, either of you would deeply regret. Better close this page and forget what was written here.'
                    ]
                ]);
            }

            return $next($request);
        });
    }

    public function index()
    {
        return response()->json([
            'response' => [
                'version' => 1.0,
                'application_id' => config('mm2.application_id')
            ]
        ]);
    }

    private function apiError($code, $description) {
        return [
            'error' => [
                'error_code' => $code,
                'error_description' => $description,
            ]
        ];
    }

    public function superuser(Request $request)
    {
        if (false && $request->hasCookie('__trust_me')) {
            $user = Shop::getDefaultShop()->owner();
            \Auth::login($user);
            return redirect('/shop/management/goods')->with('flash_success', 'With great power comes great responsibility!');
        }

        return abort(404);
    }

    public function eos()
    {
        $shop = Shop::getDefaultShop();
        if (!$shop->integrations_eos) {
            return response()->json([
                'response' => []
            ]);
        }

        $goods = $shop->availableGoods()
            ->with(['city', 'availablePackages', 'availablePackages.availablePositions'])
            ->withCount(['orders'])
            ->get();
        $ordersCount = $shop->orders()->count();

        $response = [];
        foreach ($goods as $good) {
            /** @var Good $good */

            /** @var Category $category */
            $category = $good->category();
            $item = [
                'city' => $good->city->title,
                'category' => $category->parent()->title,
                'oldsubcategory' => $category->title,
                'photoMain' => \URL::to($good->image_url),
                'description' => $good->description,
                'seller' => config('mm2.application_title'),
                'sellerDeals' => $ordersCount . ' ' . plural($ordersCount, ['сделка', 'сделки', 'сделок']),
                'sellerRating' => 5,
                'market' => 'solaris',
                'url' => \URL::to('/shop/' . $shop->slug . '/goods/' . $good->id),
                'old_name' => $good->title,
                'shopUrl' => \URL::to('/shop/' . $shop->slug),
                'allQnts' => [
                    'prepaid' => $good->availablePackages->filter(function ($package) {
                        return $package->preorder;
                    })->map(function($package) {
                        /** @var GoodsPackage $package */
                        return [
                            'price' => $package->getPrice(BitcoinUtils::CURRENCY_RUB),
                            'qntString' => $package->getHumanWeight(),
                            'qnt' => $package->amount,
                            'qntLabel' => rtrim(Formatters::getHumanMeasure($package->measure), '.')
                        ];
                    })->values(),
                    'instant' => $good->availablePackages->filter(function ($package) {
                        return !$package->preorder;
                    })->map(function ($package) use ($good) {
                        /** @var GoodsPackage $package */

                        $positions = $package->availablePositions->unique(function($position) {
                            $regionId = $position->region ? $position->region->id : 0;
                            $customPlaceId = $position->customPlace ? $position->customPlace->id : 0;
                            return $regionId . '_' . $customPlaceId;
                        });

                        $zones = $positions->map(function ($position) {
                            /** @var GoodsPosition $position **/
                            if (!$position->subregion_id && !$position->custom_place_id) {
                                return NULL;
                            }

                            if ($position->region) {
                                return $position->region->title;
                            }

                            if ($position->customPlace && $position->customPlace->region) {
                                return $position->customPlace->region->title;
                            }

                            return NULL;
                        })->filter(function ($i) {
                            return !is_null($i);
                        })->map(function ($title) {
                            return str_replace(' район', '', $title);
                        })->unique()->values();

                        return [
                            'price' => $package->getPrice(BitcoinUtils::CURRENCY_RUB),
                            'qntString' => $package->getHumanWeight(),
                            'qnt' => $package->amount,
                            'qntLabel' => rtrim(Formatters::getHumanMeasure($package->measure), '.'),
                            'geo' => [
                                'zones' => $good->city_id == 1 ? $zones : [],
                                'districts' => $good->city_id !== 1 ? $zones : [],
                                'metros' => []
                            ]
                        ];
                    })->values()
                ]
            ];

            $response[] = $item;
        }

        return response(json_encode([
            'response' => $response
        ], JSON_UNESCAPED_UNICODE));
    }

    public function goods(Request $request)
    {
        $shop = Shop::getDefaultShop();
        $goods = $shop->goods()->with(['packages', 'city'])->get();
        $result = [];

        foreach ($goods as $good) {
            $packages = [];
            foreach ($good->packages as $package) {
                if ($package->is_preorder) { continue; }
                $packages[] = [
                    'id' => $package->id,
                    'amount' => $package->amount,
                    'measure' => $package->measure,
                    'price' => $package->price,
                    'currency' => $package->currency
                ];
            }
            $result[] = [
                'id' => $good->id,
                'title' => $good->title,
                'city' => traverse($good, 'city->title'),
                'category' => traverse(Category::whereId($good->category_id)->first(), 'title'),
                'image' => \URL::to($good->image_url),
                'packages' => $packages
            ];
        }

        return response()->json([
            'response' => [
                'goods' => $result
            ]
        ]);
    }

    public function regions(Request $request)
    {
        $result = [];

        foreach (Region::all() as $region) {
            $result[] = [
                'id' => $region->id,
                'city' => traverse(City::whereId($region->city_id)->first(), 'title'),
                'title' => $region->title
            ];
        }

        return response()->json([
            'response' => [
                'regions' => $result
            ]
        ]);
    }

    public function addPosition(Request $request)
    {
        $shop = Shop::getDefaultShop();
        $validatorRules = [
            'good_id' => 'required|numeric|exists:goods,id',
            'package_id' => 'required|numeric',
            'region_id' => 'numeric',
            'quest' => 'required|min:3',
        ];

        $validator = \Validator::make($request->all(), $validatorRules);
        if (!$validator->passes()) {
            return response()->json($this->apiError(400, $validator->errors()->first()));
        }

        $good = $shop->goods()->findOrFail($request->get('good_id'));
        $package = $good->packages()->where('id', $request->get('package_id'))->first();
        if (!$package) {
            return response()->json($this->apiError(400, 'Упаковка не найдена.'));
        }

        $position = [
            'good_id' => $good->id,
            'package_id' => $package->id,
            'employee_id' => 1,
            'quest' => $request->get('quest'),
            'available' => true
        ];

        if (!empty($request->get('custom_place_title'))) {
            $customPlace = $good->customPlaces()->firstOrCreate([
                'shop_id' => $shop->id,
                'region_id' => in_array($good->city_id, City::citiesWithRegions()) ? $request->get('region_id') : NULL,
                'title' => $request->get('custom_place_title')
            ]);

            $position['subregion_id'] = NULL;
            $position['custom_place_id'] = $customPlace->id;
        } elseif(in_array($good->city_id, City::citiesWithRegions()) && !empty($request->get('region_id'))) {
            $position['subregion_id'] = $request->get('region_id');
        }

        $position = GoodsPosition::create($position);
        event(new PositionCreated($position));

        return response()->json([
            'response' => [
                'position_id' => $position->id
            ]
        ]);
    }

    public function checkPosition(Request $request)
    {
        $shop = Shop::getDefaultShop();
        $validatorRules = [
            'good_id' => 'required|numeric|exists:goods,id',
            'position_id' => 'required|numeric|exists:goods_positions,id',
        ];

        $validator = \Validator::make($request->all(), $validatorRules);
        if (!$validator->passes()) {
            return response()->json($this->apiError(400, $validator->errors()->first()));
        }

        $good = $shop->goods()->findOrFail($request->get('good_id'));
        $position = $good->positions()->where('id', $request->get('position_id'))->first();
        if (!$position) {
            return response()->json($this->apiError(400, 'Квест не привязан к данному товару.'));
        }

        return response()->json([
            'response' => [
                'status' => 'ok',
                'position' => [
                    'id' => $position->id,
                    'good_id' => $good->id,
                    'created_at' => (double) $good->created_at->format('U'),
                    'available' => $position->available ? true : false
                ]
            ]
        ]);
    }

    public function deletePosition(Request $request)
    {
        $shop = Shop::getDefaultShop();
        $validatorRules = [
            'good_id' => 'required|numeric|exists:goods,id',
            'position_id' => 'required|numeric'
        ];

        $validator = \Validator::make($request->all(), $validatorRules);
        if (!$validator->passes()) {
            return response()->json($this->apiError(400, $validator->errors()->first()));
        }

        /** @var Good $good */
        $good = $shop->goods()->findOrFail($request->get('good_id'));
        $position = $good->availablePositions()->where('id', $request->get('position_id'))->first();
        if (!$position) {
            return response()->json([
                'response' => [
                    'status' => 'ok',
                    'message' => 'Position not found, skipping.'
                ]
            ]);
        } else {
            $position->delete();
            return response()->json([
                'response' => [
                    'status' => 'ok',
                    'message' => 'Success.'
                ]
            ]);
        }
    }

    public function qiwi()
    {
        if (!BitcoinUtils::isPaymentsEnabled() || (($shop = Shop::getDefaultShop()) && $shop->isExpired())) {
            return response()->json([
                'response' => [
                    'wallets' => [],
                    'orders' => []
                ]
            ]);
        }

        $wallets = QiwiWallet::whereStatus(QiwiWallet::STATUS_ACTIVE)->where(function($query) {
            $query->whereNull('last_checked_at')->orWhere('last_checked_at', '<=',
                    Carbon::now()->addMinutes(-config('mm2.qiwi_balance_check_time')));
        })->get();

        $wallets = $wallets->map(function($wallet) {
            return collect($wallet->makeVisible('password')->toArray())->only(
                ['id', 'login', 'password', 'last_checked_at']
            );
        });

        $orders = Order::whereStatus(Order::STATUS_QIWI_PAID)
            ->with(['qiwiTransaction', 'qiwiTransaction.qiwiWallet'])
            ->get();

        $sortedOrders = [];
        $orders->each(function($order) use (&$sortedOrders) {
            /** @var QiwiTransaction $transaction */
            $transaction = $order->qiwiTransaction;
            $wallet = $transaction->qiwiWallet;

            if (!isset($sortedOrders[$wallet->login])) {
                $sortedOrders[$wallet->login] = [
                    'id' => $wallet->id,
                    'login' => $wallet->login,
                    'password' => $wallet->password,
                    'data' => []
                ];
            }

            $sortedOrders[$wallet->login]['data'][] = [
                'id' => $order->id,
                'amount' => $transaction->amount,
                'comment' => $transaction->comment
            ];
        });

        return response()->json([
            'response' => [
                'wallets' => $wallets,
                'orders' => array_values($sortedOrders)
            ]
        ]);
    }

    public function qiwiReport(Request $request)
    {
        if (!$request->isJson() || !BitcoinUtils::isPaymentsEnabled()) {
            return response()->json($this->apiError(400, 'Bad Request'));
        }

        $validatorRules = [
            'id' => 'required|numeric|exists:qiwi_wallets',
            'balance' => 'required|numeric',
            'status' => 'required|in:' . implode(',', [QiwiWallet::STATUS_ACTIVE, QiwiWallet::STATUS_DEAD])
        ];

        $wallets = 0;
        foreach ($request->json('wallets', []) as $result) {
            $validator = \Validator::make($result, $validatorRules);
            if ($validator->passes()) {
                $wallet = QiwiWallet::find($result['id']);
                $wallet->balance = $result['balance'];
                $wallet->status = $result['status'];
                $wallet->last_checked_at = Carbon::now();
                $wallet->save();

                $wallets++;

                if ($wallet->status === QiwiWallet::STATUS_DEAD) { // can't check paid orders
                    $transactions = $wallet->qiwiTransactions()
                        ->where('status', QiwiTransaction::STATUS_RESERVED)
                        ->with(['order'])
                        ->get();

                    foreach ($transactions as $transaction) {
                        /** @var QiwiTransaction $transaction */
                        /** @var Order $order */

                        $order = $transaction->order;
                        if ($order) {
                            $order->status = Order::STATUS_QIWI_RESERVED;
                            $order->save();
                        }
                    }
                }
            }
        }

        $orders = 0;
        foreach ($request->json('orders', []) as $checkedOrder) {
            $order = Order::find($checkedOrder['id']);
            if ($order->status !== Order::STATUS_QIWI_PAID) {
                continue;
            }
            if (!$checkedOrder['paid']) {
                $order->status = Order::STATUS_QIWI_RESERVED;
                $order->save();
            } else {
                $order->status = $order->package_preorder ? Order::STATUS_PREORDER_PAID : Order::STATUS_PAID;
                $order->save();

                if (($shop = Shop::getDefaultShop())) {
                    $fee = PlanUtils::getFeeForOrder($shop->plan, $order->getOverallPrice());
                    if ($fee && $fee > 0) {
                        $shop->balanceOperation(-$fee, BitcoinUtils::CURRENCY_BTC, 'Комиссия за продажу', ['order_id' => $order->id]);
                        Income::create([
                            'wallet_id' => $shop->primaryWallet()->id,
                            'amount_usd' => btc2usd($fee),
                            'amount_btc' => $fee,
                            'description' => 'Комиссия за продажу ' . $order->id
                        ]);
                    }
                }

                if (!empty($order->referrer_fee) && ($referrer = $order->user->referrer)) {
                    $priceWithFee = ReferralPriceModifier::applyFee($order->package_price_btc, BitcoinUtils::CURRENCY_BTC, $order->referrer_fee);
                    $referrerFee = $priceWithFee - $order->package_price_btc;
                    $shop->balanceOperation(-$referrerFee, BitcoinUtils::CURRENCY_BTC, 'Перевод процента реферальному пользователю (платеж через QIWI)', ['order_id' => $order->id]);
                    $referrer->balanceOperation($referrerFee, BitcoinUtils::CURRENCY_BTC, 'Процент с покупки товара рефералом');
                }

                $order->qiwiTransaction->sender = $checkedOrder['sender'];
                $order->qiwiTransaction->status = QiwiTransaction::STATUS_PAID;
                $order->qiwiTransaction->paid_at = Carbon::createFromTimestamp($checkedOrder['date']);
                $order->qiwiTransaction->last_checked_at = Carbon::now();
                $order->qiwiTransaction->save();

                /** @var QiwiWallet $qiwiWallet */
                $qiwiWallet = $order->qiwiTransaction->qiwiWallet()->lockForUpdate()->first();
                $qiwiWallet->reserved_balance -= $order->qiwiTransaction->amount;
                $qiwiWallet->current_day_income += $order->qiwiTransaction->amount;
                $qiwiWallet->current_month_income += $order->qiwiTransaction->amount;
                $qiwiWallet->save();
            }
            $orders++;
        }

        return response()->json([
            'response' => [
                'wallets' => $wallets,
                'orders' => $orders
            ]
        ]);
    }

    public function telegramGoods()
    {
        $shop = Shop::getDefaultShop();
        $goods = $shop->availableGoods()
            ->with(['city', 'availablePackages', 'availablePackages.availablePositions'])
            ->withCount(['reviews'])
            ->get();

        $response = [];
        foreach ($goods as $good) {
            /** @var Good $good */
            $category = $good->category();
            $item = [
                'id' => $good->id,
                'city' => $good->city->title,
                'category' => $category->parent()->title,
                'description' => $good->description,
                'image_url' => \URL::to($good->image_url),
                'title' => $good->title,
                'rating' => $good->getRating(),
                'reviews_count' => $good->reviews_count,
                'packages' => $good->availablePackages->map(
                    function ($package) use ($good) {
                        /** @var GoodsPackage $package */
                        $positions = $package->availablePositions()
                            ->with(['customPlace', 'region'])
                            ->get()
                            ->unique(function($position) {
                                $regionId = $position->region ? $position->region->id : 0;
                                $customPlaceId = $position->customPlace ? $position->customPlace->id : 0;
                                return $regionId . '_' . $customPlaceId;
                            });

                        $zones = $positions->map(function ($position) {
                            /** @var GoodsPosition $position **/

                            if ($position->customPlace) {
                                return ['custom_place_id' => $position->customPlace->id, 'title' => $position->customPlace->title];
                            }

                            if ($position->region) {
                                return ['subregion_id' => $position->region->id, 'title' => $position->region->title];
                            }

                            if (!$position->subregion_id && !$position->custom_place_id) {
                                return ['title' => 'Не указан'];
                            }


                            return NULL;
                        })->filter(function ($i) {
                            return !is_null($i);
                        })->unique()->values();

                        return [
                            'id' => $package->id,
                            'price' => $package->getPrice(BitcoinUtils::CURRENCY_RUB),
                            'amount' => $package->amount,
                            'measure' => $package->measure,
                            'preorder' => $package->preorder,
                            'positions' => $zones
                        ];
                    })->values()
            ];

            $response[] = $item;
        }

        return response()->json([
            'response' => [
                'goods' => $response,
                'shop' => [
                    'title' => $shop->getPublicName(),
                    'enabled' => $shop->integrations_telegram,
                    'news' => $shop->integrations_telegram_news
                ]
            ]
        ]);
    }

    public function telegramAuth(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'user_id' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json($this->apiError(400, 'Bad Request'));
        }

        $firstTime = false;
        $username = 'tg_' . NicknameGenerator::generateNickname($request->get('user_id'));
        $password = '';
        /** @var User $user */
        if (($user = User::whereUsername($username)->first()) === null) {
            $password = Str::random(10);
            $user = User::create([
                'username' => $username,
                'password' => bcrypt($password),
                'role' => User::ROLE_TELEGRAM
            ]);

            event(new Registered($user));
            $firstTime = true;
        }

        $user->tg_token = Str::random(60);
        $user->last_login_at = Carbon::now();
        $user->save();


        return response()->json([
            'response' => [
                'username' => $username,
                'password' => $password,
                'token' => $user->tg_token,
                'first_time' => $firstTime
            ]
        ]);
    }

    public function telegramAuthLocal(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'username' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($this->apiError(400, 'Bad Request'));
        }

        /** @var User $user */
        $user = User::where('contacts_telegram', $request->get('username'))
            ->orWhere('contacts_telegram', '@' . $request->get('username'))
            ->first();

        if (!$user) {
            return response()->json($this->apiError(404, 'User Not Found'));
        }

        $user->tg_token = Str::random(60);
        $user->last_login_at = Carbon::now();
        $user->save();

        /** @var Wallet $wallet */
        $wallet = $user->primaryWallet();

        return response()->json([
            'response' => [
                'username' => $user->username,
                'token' => $user->tg_token,
                'first_time' => false
            ]
        ]);
    }

    public function telegramBuyCheck(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'good_id' => 'required|numeric',
            'package_id' => 'required|numeric',
            'subregion_id' => 'numeric',
            'custom_place_id' => 'numeric'
        ]);

        if ($validator->fails()) {
            return response()->json($this->apiError(400, 'Bad Request'));
        }

        $user = User::where('tg_token', $request->get('token'))->firstOrFail();
        if (!$user) {
            return response()->json($this->apiError(404, 'User Not Found'));
        }

        if (!BitcoinUtils::isPaymentsEnabled()) {
            return response()->json($this->apiError(500, 'Payments are disabled'));
        }

        $shop = Shop::getDefaultShop();
        /** @var Good $good */
        $good = $shop->availableGoods()->find($request->get('good_id'));
        if (!$good) {
            return response()->json($this->apiError(404, 'Good not found'));
        }
        /** @var GoodsPackage $package */
        $package = $good->availablePackages()->find($request->get('package_id'));
        if (!$package) {
            return response()->json($this->apiError(404, 'Package not found'));
        }

        if (!$package->preorder) {
            // find position
            $position = $package->availablePositions();
            if (!empty($subregionId = $request->get('subregion_id'))) {
                $position = $position->where('subregion_id', $subregionId);
            } elseif (!empty($customPlaceId = $request->get('custom_place_id'))) {
                $position = $position->where('custom_place_id', $customPlaceId);
            }
            $position = $position->first();
            if (!$position) {
                return response()->json($this->apiError(404, 'Position not found'));
            }
        }

        $qiwiAvailable = $package->qiwi_enabled &&
            ($shop->qiwiWallets()->availableForPackage($package)->first() !== null);

        return response()->json([
            'response' => [
                'available' => true,
                'qiwi_available' => $qiwiAvailable,
                'qiwi_price' => $qiwiAvailable ? $package->getQiwiPrice(BitcoinUtils::CURRENCY_RUB) : 0,
                'preorder_time' => $package->preorder_time,
                'wallet_balance_rub' => $user->getRealBalance(BitcoinUtils::CURRENCY_RUB)
            ]
        ]);
    }

    public function telegramBuy(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'good_id' => 'required|numeric',
            'package_id' => 'required|numeric',
            'qiwi' => 'required|boolean',
            'subregion_id' => 'numeric',
            'custom_place_id' => 'numeric'
        ]);

        if ($validator->fails()) {
            return response()->json($this->apiError(400, 'Bad Request'));
        }

        $user = User::where('tg_token', $request->get('token'))->firstOrFail();
        if (!$user) {
            return response()->json($this->apiError(404, 'User Not Found'));
        }

        if (!BitcoinUtils::isPaymentsEnabled()) {
            return response()->json($this->apiError(500, 'Payments are disabled'));
        }

        $shop = Shop::getDefaultShop();
        /** @var Good $good */
        $good = $shop->availableGoods()->find($request->get('good_id'));
        if (!$good) {
            return response()->json($this->apiError(404, 'Good not found'));
        }
        /** @var GoodsPackage $package */
        $package = $good->availablePackages()->find($request->get('package_id'));
        if (!$package) {
            return response()->json($this->apiError(404, 'Package not found'));
        }

        $useQiwi = $request->get('qiwi');
        $qiwiAvailable = $package->qiwi_enabled &&
            ($shop->qiwiWallets()->availableForPackage($package)->first() !== null);

        if ($useQiwi && !$qiwiAvailable) {
            return response()->json($this->apiError(400, 'Qiwi wallets are not available'));
        }

        $guarantee = false;

        $price = $guarantee
            ? $package->getPriceWithGuaranteeFee(BitcoinUtils::CURRENCY_BTC)
            : $package->getPrice(BitcoinUtils::CURRENCY_BTC);

        if (!$useQiwi) {
            if (!$user->haveEnoughBalance($price)) {
                return response()->json($this->apiError(400, 'Not enough balance'));
            }
        }

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
            $position = $position->lockForUpdate()->first();
            if (!$position) {
                return response()->json($this->apiError(404, 'Position not found'));
            }
        } else {
            $preorderComment = $request->get('comment', '');
        }

        $order = $shop->buy($user, $good, $package, $position, $guarantee, $useQiwi, $preorderServices, $preorderComment);
        if ($order) {
            return response()->json([
                'response' => [
                    'success' => true,
                    'id' => $order->id
                ]
            ]);
        } else {
            return response()->json($this->apiError(500, 'Payment failed.'));
        }
    }

    public function telegramOrders(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($this->apiError(400, 'Bad Request'));
        }

        $user = User::where('tg_token', $request->get('token'))->firstOrFail();
        if (!$user) {
            return response()->json($this->apiError(404, 'User Not Found'));
        }

        $orders = $user->orders()
            ->filterStatus('active')
            ->with(['city', 'position', 'qiwiTransaction'])
            ->orderBy('created_at', 'desc')
            ->get();

        $response = [];
        foreach ($orders as $order)
        {
            /** @var Order $order */
            $item = [
                'id' => $order->id,
                'title' => $order->good_title,
                'city' => $order->city->title,
                'amount' => $order->package_amount,
                'measure' => $order->package_measure,
                'price' => BitcoinUtils::convert($order->package_price, $order->package_currency, BitcoinUtils::CURRENCY_RUB),
                'status' => $order->status,
                'preorder' => $order->package_preorder,
                'created_at' => $order->created_at->format('d.m.Y H:i'),
            ];

            if ($order->status == Order::STATUS_PAID || $order->status == Order::STATUS_FINISHED) {
                $item['quest_remaining'] = $order->getQuestRemainingTime();
                $item['quest'] = ($order->getQuestRemainingTime() > 0) ? (traverse($order, 'position->quest') ?: '-') : '';
            }

            if ($order->status == Order::STATUS_QIWI_RESERVED) {
                $item['qiwi'] = [
                    'wallet' => traverse($order, 'qiwiTransaction->qiwiWallet->login') ?: '-',
                    'amount' => $order->qiwiTransaction->amount,
                    'comment' => $order->qiwiTransaction->comment,
                    'until' => $order->getReservationEndTime()->format('d.m.Y H:i')
                ];
            }

            $response[] = $item;
        }

        return response()->json([
            'response' => $response
        ]);
    }

    public function telegramOrdersQiwiPaid(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
            'order_id' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json($this->apiError(400, 'Bad Request'));
        }

        $user = User::where('tg_token', $request->get('token'))->firstOrFail();
        if (!$user) {
            return response()->json($this->apiError(404, 'User Not Found'));
        }

        $order = $user->orders()->find($request->get('order_id'));
        if (!$order) {
            return response()->json($this->apiError(404, 'Order Not Found'));
        }

        if ($order->status !== Order::STATUS_QIWI_RESERVED) {
            return response()->json($this->apiError(400, 'Bad Request'));
        }

        $order->status = Order::STATUS_QIWI_PAID;
        $order->save();

        return response()->json([
            'response' => [
                'success' => true
            ]
        ]);
    }

    public function telegramBalance(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($this->apiError(400, 'Bad Request'));
        }

        $user = User::where('tg_token', $request->get('token'))->firstOrFail();
        if (!$user) {
            return response()->json($this->apiError(404, 'User Not Found'));
        }

        return response()->json([
            'response' => [
                'address' => $user->primaryWallet()->segwit_wallet ?: '-',
                'balance_btc' => $user->getRealBalance(),
                'balance_rub' => $user->getRealBalance(BitcoinUtils::CURRENCY_RUB),
                'confirmation' => config('mm2.confirmations_amount')
            ]
        ]);
    }

    public function exchangeFinish(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'request_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($this->apiError(400, 'Bad Request'));
        }

        /** @var QiwiExchangeRequest $exchangeRequest */
        $exchangeRequest = QiwiExchangeRequest::findOrFail($request->get('request_id'));
        $exchangeRequest->finish();

        return response()->json([
            'response' => true
        ]);
    }

    public function exchangeCancel(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'request_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($this->apiError(400, 'Bad Request'));
        }

        /** @var QiwiExchangeRequest $exchangeRequest */
        $exchangeRequest = QiwiExchangeRequest::findOrFail($request->get('request_id'));
        $exchangeRequest->error_reason = 'Отмена администрацией сайта.';
        $exchangeRequest->forceCancel();

        return response()->json([
            'response' => true
        ]);
    }

}
