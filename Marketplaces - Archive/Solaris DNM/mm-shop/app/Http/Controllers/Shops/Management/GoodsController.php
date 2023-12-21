<?php
/**
 * File: GoodsController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 * 2016.
 */

namespace App\Http\Controllers\Shops\Management;


use App\City;
use App\CustomPlace;
use App\EmployeesLog;
use App\Events\PositionCreated;
use App\Events\PositionDeleted;
use App\Good;
use App\GoodsCity;
use App\GoodsPackage;
use App\GoodsPackagesService;
use App\GoodsPhoto;
use App\GoodsPosition;
use App\GoodsReview;
use App\Http\Requests\ShopGoodsAddRequest;
use App\Http\Requests\ShopGoodsCitiesRequest;
use App\Http\Requests\ShopGoodsEditRequest;
use App\Http\Requests\ShopGoodsPackageAddRequest;
use App\Http\Requests\ShopGoodsPackageEditRequest;
use App\Http\Requests\ShopGoodsPlacesAddRequest;
use App\Http\Requests\ShopGoodsQuestAddRequest;
use App\Http\Requests\ShopGoodsQuestEditRequest;
use App\Http\Requests\ShopPaidServiceAddRequest;
use App\Http\Requests\ShopPaidServiceEditRequest;
use App\PaidService;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

class GoodsController extends ManagementController
{
    public function __construct()
    {
        parent::__construct();
        \View::share('page', 'goods');
    }

    public function index(Request $request)
    {
        $goods = $this->shop->goods()
            ->with(['shop', 'cities', 'packages', 'orders'])
            ->withCount(['availablePositions'])
            ->applySearchFilters($request)
            ->orderBy(\DB::raw('priority is null'))
            ->orderBy('priority', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $goods = $goods->filter(function ($good) {
            $user = \Auth::user();
            return $user->can('management-quests-create', $good) ||
                $user->can('management-goods-edit', $good) ||
                $user->can('management-goods-delete', $good);
        });

        return view('shop.management.goods.index', [
            'goods' => $goods
        ]);
    }
    
    public function showAddForm()
    {
        $this->authorize('management-goods-create');

        return view('shop.management.goods.add');
    }
    
    public function add(ShopGoodsAddRequest $request)
    {
        $this->authorize('management-goods-create');

        $good = Good::create([
            'shop_id' => $this->shop->id,
            'category_id' => $request->get('category'),
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'image_url' => $request->imageURL,
            'priority' => $request->get('priority', NULL)
        ]);

        if (count($request->additionalImagesURL) > 0) {
            foreach ($request->additionalImagesURL as $additionalImageURL) {
                GoodsPhoto::create([
                    'good_id' => $good->id,
                    'image_url' => $additionalImageURL
                ]);
            }
        }

        EmployeesLog::log(\Auth::user(), EmployeesLog::ACTION_GOODS_ADD,
            ['good_id' => $good->id],
            ['good_title' => $good->title]);

        return redirect('/shop/management/goods')->with('flash_success', 'Товар успешно добавлен!');
    }

    public function showEditForm(Request $request, $goodId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        $this->authorize('management-goods-edit', $good);

        return view('shop.management.goods.edit', [
            'good' => $good
        ]);
    }

    public function edit(ShopGoodsEditRequest $request, $goodId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        $this->authorize('management-goods-edit', $good);

        $good->update([
            'shop_id' => $this->shop->id,
            'category_id' => $request->get('category') ?: $good->category_id,
            'title' => $request->get('title') ?: $good->title,
            'description' => $request->get('description') ?: $good->description,
            'image_url' => $request->imageURL ?: $good->image_url,
            'priority' => $request->get('priority')
        ]);

        if (count($request->additionalImagesURL) > 0) {
            GoodsPhoto::whereGoodId($goodId)->delete();
            foreach ($request->additionalImagesURL as $additionalImageURL) {
                GoodsPhoto::create([
                    'good_id' => $good->id,
                    'image_url' => $additionalImageURL
                ]);
            }
        }

        EmployeesLog::log(\Auth::user(), EmployeesLog::ACTION_GOODS_EDIT,
            ['good_id' => $good->id],
            ['good_title' => $good->title]);

        return redirect('/shop/management/goods')->with('flash_success', 'Товар успешно отредактирован!');
    }

    public function showDeleteForm(Request $request, $goodId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        $this->authorize('management-goods-delete', $good);

        return view('shop.management.goods.delete', [
            'good' => $good
        ]);
    }

    public function delete(Request $request, $goodId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        $this->authorize('management-goods-delete', $good);

        EmployeesLog::log(\Auth::user(), EmployeesLog::ACTION_GOODS_DELETE,
            ['good_id' => $good->id],
            ['good_title' => $good->title]);

        $good->delete();

        return redirect('/shop/management/goods')->with('flash_success', 'Товар успешно удален!');
    }

    public function showCloneForm(Request $request, $goodId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        $this->authorize('management-goods-create');
        $this->authorize('management-goods-edit', $good);

        return view('shop.management.goods.clone', [
            'good' => $good
        ]);
    }

    public function doClone(Request $request, $goodId) // clone is reserved word
    {
        /** @var Good $oldGood */
        $oldGood = $this->shop->goods()->findOrFail($goodId);

        $this->authorize('management-goods-create');
        $this->authorize('management-goods-edit', $oldGood);

        $newGoodTitle = $oldGood->title . ' - копия';

        if ($this->shop->goods()->where('title', $newGoodTitle)->exists()) {
            return redirect('/shop/management/goods/edit/' . $goodId)->with('flash_warning', 'Товар уже был клонирован ранее. Пожалуйста, измените его имя и попробуйте еще раз.');
        }

        /** @var Good $newGood */
        $newGood = Good::create([
            'shop_id' => $this->shop->id,
            'city_id' => $oldGood->city_id,
            'category_id' => $oldGood->category_id,
            'title' => $newGoodTitle,
            'description' => $oldGood->description,
            'image_url' => $oldGood->image_url,
            'priority' => $oldGood->priority
        ]);

        foreach ($oldGood->photos as $oldGoodPhoto)
        {
            GoodsPhoto::create([
                'good_id' => $newGood->id,
                'image_url' => $oldGoodPhoto->image_url
            ]);
        }

        foreach ($oldGood->packages()->with(['packageServices'])->get() as $oldGoodPackage)
        {
            /** @var GoodsPackage $oldGoodPackage */

            /** @var GoodsPackage $newGoodPackage */
            $newGoodPackage = GoodsPackage::create([
                'shop_id' => $this->shop->id,
                'good_id' => $newGood->id,
                'amount' => $oldGoodPackage->amount,
                'measure' => $oldGoodPackage->measure,
                'price' => $oldGoodPackage->price,
                'currency' => $oldGoodPackage->currency,
                'qiwi_enabled' => $oldGoodPackage->qiwi_enabled,
                'qiwi_price' => $oldGoodPackage->qiwi_price,
                'preorder' => $oldGoodPackage->preorder,
                'preorder_time' => $oldGoodPackage->preorder_time,
                'employee_reward' => $oldGoodPackage->employee_reward,
                'employee_penalty' => $oldGoodPackage->employee_penalty,
                'has_quests' => $oldGoodPackage->preorder
            ]);

            if ($newGoodPackage->preorder) {
                foreach ($oldGoodPackage->packageServices as $oldGoodPackageService)
                {
                    /** @var GoodsPackagesService $oldGoodPackageService */
                    GoodsPackagesService::create([
                        'package_id' => $newGoodPackage->id,
                        'service_id' => $oldGoodPackageService->id
                    ]);
                }
            }
        }

        EmployeesLog::log(\Auth::user(), EmployeesLog::ACTION_GOODS_ADD,
            ['good_id' => $newGood->id],
            ['good_title' => $newGood->title]);

        return redirect('/shop/management/goods/edit/' . $newGood->id)->with('flash_success', 'Товар успешно склонирован.');
    }

    public function packages($goodId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->with(['cities'])->findOrFail($goodId);

        if (!\Auth::user()->can('management-goods-edit', $good) && !\Auth::user()->can('management-quests-create', $good)) {
            abort(403);
        }

        $cities = $good->cities;
        return view('shop.management.goods.packages.index', [
            'good' => $good,
            'cities' => $cities
        ]);
    }

    public function packagesInCity($goodId, $cityId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var City $city */
        $city = City::findOrFail($cityId);

        if (!\Auth::user()->can('management-goods-edit', $good) && !\Auth::user()->can('management-quests-create', [$good, $city])) {
            abort(403);
        }

        $packages = $good->packages()->where('city_id', $cityId)->orderBy('amount')->orderBy('measure')->get();
        return view('shop.management.goods.packages.city', [
            'good' => $good,
            'city' => $city,
            'packages' => $packages
        ]);
    }

    public function showPackageAddForm(Request $request, $goodId, $cityId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var City $city */
        $city = City::findOrFail($cityId);

        $this->authorize('management-goods-create', $good);

        /** @var PaidService[] $services */
        $services = $this->shop->services()->get();

        return view('shop.management.goods.packages.add', [
            'good' => $good,
            'city' => $city,
            'services' => $services,
            'pakCount' => min($request->get('count', 1), 10)
        ]);
    }

    public function packageAdd(ShopGoodsPackageAddRequest $request, $goodId, $cityId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);
        $count = min($request->input('count', 1), 10);
        /** @var City $city */
        City::findOrFail($cityId);
        $this->authorize('management-goods-create', $good);

        for ($i = 0; $i < $count; $i++) {
            $package = [
                'shop_id' => $this->shop->id,
                'good_id' => $good->id,
                'city_id' => $cityId,
                'amount' => $request->input("packages.$i.amount"),
                'measure' => $request->input("packages.$i.measure"),
                'price' => $request->input("packages.$i.price"),
                'currency' => $request->input("packages.$i.currency"),
                'net_cost' => $request->has("packages.$i.preorder") ? NULL : $request->input("packages.$i.net_cost") ?: NULL,
                'qiwi_enabled' => $request->has("packages.$i.qiwi_enabled"),
                'qiwi_price' => $request->input("packages.$i.qiwi_price") ?: NULL,
                'preorder' => $request->has("packages.$i.preorder"),
                'preorder_time' => $request->has("packages.$i.preorder") ? $request->input("packages.$i.preorder_time") : NULL,
                'employee_reward' => $request->input("packages.$i.employee_reward"),
                'employee_penalty' => $request->input("packages.$i.employee_penalty"),
                'has_quests' => $request->has("packages.$i.preorder")
            ];

            $package = GoodsPackage::firstOrCreate(
                [
                    'shop_id' => $this->shop->id,
                    'good_id' => $good->id,
                    'city_id' => $cityId,
                    'amount' => $request->input("packages.$i.amount"),
                    'measure' => $request->input("packages.$i.measure"),
                    'preorder' => $request->has("packages.$i.preorder"),
                ],
                $package
                );

            if ($package->preorder) {
                foreach ($request->get("packages.$i.services", []) as $serviceId) {
                    GoodsPackagesService::firstOrCreate([
                        'package_id' => $package->id,
                        'service_id' => $serviceId
                    ]);
                }
            }

            EmployeesLog::log(\Auth::user(), EmployeesLog::ACTION_PACKAGES_ADD,
                ['good_id' => $good->id, 'package_id' => $package->id],
                ['good_title' => $good->title]);
        }

        return redirect('/shop/management/goods/packages/city/' . $goodId . '/' . $cityId)->with('flash_success', 'Упаковка успешно добавлена!');
    }

    public function showPackageEditForm(Request $request, $goodId, $packageId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        $this->authorize('management-goods-edit', $good);

        /** @var GoodsPackage $package */
        $package = $good->packages()->findOrFail($packageId);

        /** @var PaidService[] $services */
        $services = $this->shop->services()->get();

        return view('shop.management.goods.packages.edit', [
            'good' => $good,
            'package' => $package,
            'services' => $services
        ]);
    }

    public function packageEdit(ShopGoodsPackageEditRequest $request, $goodId, $packageId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        $this->authorize('management-goods-edit', $good);

        /** @var GoodsPackage $package */
        $package = $good->packages()->findOrFail($packageId);

        $package->update([
            'amount' => $request->get('amount') ?: $package->amount,
            'measure' => $request->get('measure') ?: $package->measure,
            'price' => $request->get('price') ?: $package->price,
            'currency' => $request->get('currency') ?: $package->currency,
            'net_cost' => $request->has('preorder') ? NULL : $request->get('net_cost') ?: NULL,
            'qiwi_enabled' => $request->has('qiwi_enabled'),
            'qiwi_price' => $request->get('qiwi_price') ?: NULL,
            'preorder' => $request->has('preorder'),
            'preorder_time' => $request->has('preorder_time') ? $request->get('preorder_time') : NULL,
            'employee_reward' => $request->get('employee_reward') ?: NULL,
            'employee_penalty' => $request->get('employee_penalty') ?: NULL,
            'has_quests' => $request->has('preorder') || $package->availablePositions()->count() > 0,
            'has_ready_quests' => !$request->has('preorder') && $package->availablePositions()->count() > 0
        ]);

        if ($package->preorder) {
            GoodsPackagesService::where('package_id', $packageId)
                ->whereNotIn('service_id', $request->get('services', []))
                ->delete();

            foreach ($request->get('services', []) as $serviceId) {
                GoodsPackagesService::firstOrCreate([
                    'package_id' => $packageId,
                    'service_id' => $serviceId
                ]);
            }
        }

        EmployeesLog::log(\Auth::user(), EmployeesLog::ACTION_PACKAGES_EDIT,
            ['good_id' => $good->id, 'package_id' => $package->id],
            ['good_title' => $good->title]);

        return redirect('/shop/management/goods/packages/city/' . $goodId . '/' . $package->city_id)->with('flash_success', 'Упаковка успешно отредактирована!');
    }

    public function showPackageDeleteForm(Request $request, $goodId, $packageId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        $this->authorize('management-goods-delete', $good);

        /** @var GoodsPackage $package */
        $package = $good->packages()->findOrFail($packageId);

        return view('shop.management.goods.packages.delete', [
            'good' => $good,
            'package' => $package
        ]);
    }

    public function packageDelete(Request $request, $goodId, $packageId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        $this->authorize('management-goods-delete', $good);

        /** @var GoodsPackage $package */
        $package = $good->packages()->findOrFail($packageId);

        $package->delete();

        EmployeesLog::log(\Auth::user(), EmployeesLog::ACTION_PACKAGES_DELETE,
            ['good_id' => $good->id, 'package_id' => $package->id],
            ['good_title' => $good->title]);

        return redirect('/shop/management/goods/packages/city/' . $goodId . '/' . $package->city_id)->with('flash_success', 'Упаковка успешно удалена!');
    }

    public function services(Request $request)
    {
        $this->authorize('management-sections-paid-services');

        /** @var PaidService[] $services */
        $services = $this->shop->services()->get();

        return view('shop.management.services.index', [
            'services' => $services
        ]);
    }

    public function showServiceAddForm(Request $request)
    {
        $this->authorize('management-sections-paid-services');

        return view('shop.management.services.add');
    }

    public function serviceAdd(ShopPaidServiceAddRequest $request)
    {
        $this->authorize('management-sections-paid-services');

        PaidService::create([
            'shop_id' => $this->shop->id,
            'title' => $request->get('title'),
            'price' => $request->get('price'),
            'currency' => $request->get('currency')
        ]);

        return redirect('/shop/management/goods/services')->with('flash_success', 'Платная услуга добавлена!');
    }

    public function showServiceEditForm(Request $request, $serviceId)
    {
        $this->authorize('management-sections-paid-services');

        /** @var PaidService $service */
        $service = $this->shop->services()->findOrFail($serviceId);

        return view('shop.management.services.edit', [
            'service' => $service
        ]);
    }

    public function serviceEdit(ShopPaidServiceEditRequest $request, $serviceId)
    {
        $this->authorize('management-sections-paid-services');

        /** @var PaidService $service */
        $service = $this->shop->services()->findOrFail($serviceId);

        $service->update([
            'title' => $request->get('title') ?: $service->title,
            'price' => $request->get('price') ?: $service->price,
            'currency' => $request->get('currency') ?: $service->currency
        ]);

        return redirect('/shop/management/goods/services')->with('flash_success', 'Платная услуга отредактирована!');
    }

    public function showServiceDeleteForm(Request $request, $serviceId)
    {
        $this->authorize('management-sections-paid-services');

        /** @var PaidService $service */
        $service = $this->shop->services()->findOrFail($serviceId);

        return view('shop.management.services.delete', [
            'service' => $service
        ]);
    }

    public function serviceDelete(Request $request, $serviceId)
    {
        $this->authorize('management-sections-paid-services');

        /** @var PaidService $service */
        $service = $this->shop->services()->findOrFail($serviceId);
        $service->delete();

        return redirect('/shop/management/goods/services')->with('flash_success', 'Платная услуга удалена!');
    }

    public function quests(Request $request, $goodId, $packageId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var GoodsPackage $package */
        $package = $good->packages()->findOrFail($packageId);

        $this->authorize('management-quests-create', [$good, $package->city]);

        $positions = $package->availablePositions()->with(['employee', 'employee.user', 'region', 'customPlace']);

        if (!\Auth::user()->can('management-quests-own')) {
            $positions = $positions->where('employee_id', \Auth::user()->employee->id);
        }

        $positions = $positions->get();

        return view('shop.management.goods.quests.index', [
            'good' => $good,
            'package' => $package,
            'positions' => $positions,
        ]);
    }

    public function showQuestsAddCityForm(Request $request, $goodId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        $cities = $good->cities->filter(function ($city) use ($good) {
            $user = \Auth::user();
            return $user->can('management-quests-create', [$good, $city]);
        });

        if (count($cities) == 0) {
            return redirect()->back()->with('flash_warning', 'Нет городов, куда можно добавить квест.');
        }

        return view('shop.management.goods.quests.city', [
            'good' => $good,
            'cities' => $cities
        ]);
    }

    public function showQuestAddForm(Request $request, $goodId, $cityId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var City $city */
        $city = City::findOrFail($cityId);

        $this->authorize('management-quests-create', [$good, $city]);

        $packages = $good->packages()->where('city_id', $cityId)->where('preorder', false)->get();
        if (count($packages) == 0) {
            return redirect('/shop/management/goods/packages/city/' . $goodId . '/' . $cityId)->with('flash_warning', 'Для добавления квеста сначала необходимо добавить упаковку.');
        }

        $employees = $this->shop->employees()->with(['user'])->get();
        $count = min($request->get('count', 1), 10);

        return view('shop.management.goods.quests.add', [
            'employees' => $employees,
            'good' => $good,
            'city' => $city,
            'packages' => $packages,
            'questsCount' => $count
        ]);
    }

    public function questAdd(ShopGoodsQuestAddRequest $request, $goodId, $cityId)
    {
        /** @var Good $good */
        $user = \Auth::user();
        $good = $this->shop->goods()->findOrFail($goodId);

        $city = City::findOrFail($cityId);

        $this->authorize('management-quests-create', [$good, $city]);

        $count = min($request->input('count', 1), 10);

        for ($i = 0; $i < $count; $i++) {
            $position = [
                'good_id' => $goodId,
                'package_id' => $request->input("quests.$i.package"),
                'employee_id' => \Gate::allows('management-owner') ? $request->input("quests.$i.employee") : $user->employee->id,
                'quest' => $request->input("quests.$i.quest"),
                'available' => !$user->can('management-quests-moderated'),
                'moderated' => !$user->can('management-quests-moderated')
            ];

            if (!empty($request->input("quests.$i.custom_place_title"))) {
                $customPlace = $good->customPlaces()->firstOrCreate([
                    'shop_id' => $this->shop->id,
                    'region_id' => in_array($cityId, City::citiesWithRegions()) ? $request->input("quests.$i.region") : NULL,
                    'title' => $request->input("quests.$i.custom_place_title")
                ]);

                $position['subregion_id'] = NULL;
                $position['custom_place_id'] = $customPlace->id;
            } elseif (!empty($request->input("quests.$i.custom_place"))) {
                $position['subregion_id'] = NULL;
                $position['custom_place_id'] = $request->input("quests.$i.custom_place");
            } elseif (in_array($cityId, City::citiesWithRegions()) && !empty($request->input("quests.$i.region"))) {
                $position['subregion_id'] = $request->input("quests.$i.region");
            }

            $position = GoodsPosition::create($position);
            event(new PositionCreated($position));
            EmployeesLog::log($user, EmployeesLog::ACTION_QUESTS_ADD,
                ['good_id' => $good->id, 'package_id' => $position->package_id, 'position_id' => $position->id],
                ['good_title' => $good->title]);
        }

        return redirect('/shop/management/goods/quests/add/' . $goodId . '/' . $cityId)->with('flash_success', 'Квест успешно добавлен!');
    }

    public function showQuestEditForm(Request $request, $goodId, $questId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var GoodsPosition $positions */
        if(!\Auth::user()->can('management-sections-moderate')) {
            $position = $good->availablePositions()->findOrFail($questId);
        } else {
            $position = $good->positions()->findOrFail($questId);
        }

        $this->authorize('management-quests-edit', [$good, $position->package->city]);

        $packages = $good->packages()->where('city_id', $position->package->city_id)->where('preorder', false)->get();
        if (count($packages) == 0) {
            return redirect('/shop/management/goods/packages/' . $goodId)->with('flash_warning', 'Для добавления квеста сначала необходимо добавить упаковку.');
        }

        $employees = $this->shop->employees()->with(['user'])->get();

        $this->authorize('management-quests-own', $position);

        return view('shop.management.goods.quests.edit', [
            'employees' => $employees,
            'good' => $good,
            'packages' => $packages,
            'position' => $position
        ]);
    }

    public function questEdit(Request $request, $goodId, $questId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var GoodsPosition $position */
        $position = $good->availablePositions()->findOrFail($questId);

        $this->authorize('management-quests-edit', [$good, $position->package->city]);


        $this->authorize('management-quests-own', $position);

        $packageIds = $good->packages()->where('city_id', $position->package->city_id)->pluck('id')->toArray();
        $employeeIds = $good->shop()->first()->employees()->pluck('id')->toArray();

        $this->validate($request, [
            'package' => 'required|in:' . implode(',', $packageIds),
            'employee' => [
                \Auth::user()->can('management-owner') ? 'required' : '',
                'in:' . implode(',', $employeeIds)
            ],
            'quest' => 'required|min:3'
        ]);

        $position->update([
            'package_id' => $request->get('package') ?: $position->package_id,
            'employee_id' => $request->get('employee') ?: $position->employee_id,
            'quest' => $request->get('quest') ?: $position->quest,
        ]);

        if ($request->has('custom_place_title')) {
            $customPlace = $good->customPlaces()->firstOrCreate([
                'shop_id' => $this->shop->id,
                'region_id' => in_array($position->package->city_id, City::citiesWithRegions()) ? $request->get('region') : NULL,
                'title' => $request->get('custom_place_title')
            ]);

            $position['subregion_id'] = NULL;
            $position['custom_place_id'] = $customPlace->id;
        } elseif ($request->has('custom_place')) {
            $position['subregion_id'] = NULL;
            $position['custom_place_id'] = $request->get('custom_place');
        } elseif (in_array($position->package->city_id, City::citiesWithRegions()) && $request->has('region')) {
            $position['subregion_id'] = $request->get('region');
        }


        $position->save();
        EmployeesLog::log(\Auth::user(), EmployeesLog::ACTION_QUESTS_EDIT,
            ['good_id' => $good->id, 'package_id' => $position->package_id, 'position_id' => $position->id],
            ['good_title' => $good->title]);
        return redirect('/shop/management/goods/quests/' . $goodId . '/' . $position->package_id)->with('flash_success', 'Квест успешно отредактирован!');
    }

    public function questView(Request $request, $goodId, $questId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        $this->authorize('management-quests-create', $good);

        /** @var GoodsPosition $position */
        $position = $good->availablePositions()->findOrFail($questId);

        $this->authorize('management-quests-own', $position);

        return view('shop.management.goods.quests.view', [
            'good' => $good,
            'position' => $position
        ]);
    }

    public function showQuestDeleteForm(Request $request, $goodId, $questId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var GoodsPosition $position */
        $position = $good->availablePositions()->findOrFail($questId);

        $this->authorize('management-quests-delete', [$good, $position->package->city]);
        $this->authorize('management-quests-own', $position);

        return view('shop.management.goods.quests.delete', [
            'good' => $good,
            'position' => $position
        ]);
    }

    public function questDelete(Request $request, $goodId, $questId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var GoodsPosition $position */
        $position = $good->availablePositions()->findOrFail($questId);

        $this->authorize('management-quests-delete', [$good, $position->package->city]);
        $this->authorize('management-quests-own', $position);

        EmployeesLog::log(\Auth::user(), EmployeesLog::ACTION_QUESTS_DELETE,
            ['good_id' => $good->id, 'package_id' => $position->package_id, 'position_id' => $position->id],
            ['position' => $position, 'good_title' => $good->title]);
        $position->delete();

        return redirect('/shop/management/goods/quests/' . $goodId . '/' . $position->package_id)->with('flash_success', 'Квест удален!');
    }

    public function places(Request $request, $goodId, $cityId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var City $city */
        $city = City::findOrFail($cityId);

        $this->authorize('management-goods-edit', $good);

        $customPlaces = $good->customPlaces()->with(['region'])->get();

        return view('shop.management.goods.places.index', [
            'good' => $good,
            'city' => $city,
            'customPlaces' => $customPlaces
        ]);
    }

    public function showPlaceAddForm(Request $request, $goodId, $cityId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var City $city */
        $city = City::findOrFail($cityId);

        $this->authorize('management-goods-edit', $good);

        return view('shop.management.goods.places.add', [
            'good' => $good,
            'city' => $city
        ]);
    }

    public function placeAdd(ShopGoodsPlacesAddRequest $request, $goodId, $cityId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var City $city */
        $city = City::findOrFail($cityId);

        $this->authorize('management-goods-edit', $good);

        CustomPlace::create([
            'shop_id' => $this->shop->id,
            'good_id' => $good->id,
            'region_id' => in_array($cityId, City::citiesWithRegions()) ? $request->get('region') : null,
            'title' => $request->get('title')
        ]);

        return redirect('/shop/management/goods/places/' . $goodId . '/' .  $cityId)->with('flash_success', 'Место успешно добавлено!');
    }

    public function showPlaceEditForm(Request $request, $goodId, $placeId, $cityId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var City $city */
        $city = City::findOrFail($cityId);

        $this->authorize('management-goods-edit', $good);

        /** @var CustomPlace $place */
        $place = $good->customPlaces()->findOrFail($placeId);

        return view('shop.management.goods.places.edit', [
            'good' => $good,
            'city' => $city,
            'place' => $place
        ]);
    }

    public function placeEdit(Request $request, $goodId, $placeId, $cityId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var City $city */
        $city = City::findOrFail($cityId);

        $this->authorize('management-goods-edit', $good);

        /** @var CustomPlace $place */
        $place = $good->customPlaces()->findOrFail($placeId);

        $place->update([
            'region_id' => in_array($cityId, City::citiesWithRegions()) ? $request->get('region') : null,
            'title' => $request->get('title')
        ]);

        return redirect('/shop/management/goods/places/' . $goodId . '/' . $cityId)->with('flash_success', 'Место успешно отредактировано!');
    }

    public function showPlaceDeleteForm(Request $request, $goodId, $placeId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        $this->authorize('management-goods-edit', $good);

        /** @var CustomPlace $place */
        $place = $good->customPlaces()->findOrFail($placeId);

        return view('shop.management.goods.places.delete', [
            'good' => $good,
            'place' => $place
        ]);
    }

    public function placeDelete(Request $request, $goodId, $placeId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        $this->authorize('management-goods-edit', $good);

        /** @var CustomPlace $place */
        $place = $good->customPlaces()->findOrFail($placeId);

        $place->delete();

        return redirect('/shop/management/goods/places/' . $goodId)->with('flash_success', 'Место успешно удалено!');
    }

    public function showReviewReplyForm(Request $request, $goodId, $reviewId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var GoodsReview $review */
        $review = $good->reviews()->findOrFail($reviewId);

        $this->authorize('management-reviews', $review);

        return view('shop.management.goods.reviews.reply', [
            'review' => $review
        ]);
    }

    public function reviewReply(Request $request, $goodId, $reviewId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var GoodsReview $review */
        $review = $good->reviews()->findOrFail($reviewId);

        $this->authorize('management-reviews', $review);

        $review->reply_text = $request->get('reply_text');
        $review->save();

        return redirect()->back()->with('flash_success', 'Настройки сохранены.');
    }

    public function reviewHideToggle(Request $request, $goodId, $reviewId)
    {
        if (\Session::get('_token') !== $request->get('_token')) {
            throw new TokenMismatchException();
        }

        /** @var Good $good */
        $good = $this->shop->goods()->findOrFail($goodId);

        /** @var GoodsReview $review */
        $review = $good->reviews()->findOrFail($reviewId);

        $this->authorize('management-reviews', $review);

        $review->hidden = !$review->hidden;
        $review->save();

        return redirect()->back(303)->with('flash_success', $review->hidden ? 'Отзыв скрыт.' : 'Отзыв снова отображается.');
    }

    public function showCitiesForm(Request $request, $goodId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->with(['cities'])->findOrFail($goodId);

        $this->authorize('management-goods-edit', $good);

        $cities = City::allCached();
        $toggles = [];

        foreach($cities as $city) {
            $toggles[$city->id] = false;
        }
        foreach ($good->cities as $city) {
            $toggles[$city->id] = true;
        }

        return view('shop.management.goods.cities.index', [
            'good' => $good,
            'cities' => $cities,
            'toggles' => $toggles
        ]);
    }

    public function cities(ShopGoodsCitiesRequest $request, $goodId)
    {
        /** @var Good $good */
        $good = $this->shop->goods()->with(['cities'])->findOrFail($goodId);

        $this->authorize('management-goods-edit', $good);

        GoodsCity::whereGoodId($goodId)->delete();
        foreach ($request->get('cities') as $cityId) {
            GoodsCity::create(['good_id' => $goodId, 'city_id' => $cityId]);
        }

        return redirect()->back(303)->with('flash_success', 'Настройки сохранены!');
    }

    public function showModerationForm(Request $request)
    {
        $this->authorize('management-sections-moderate');

        $regions = collect([]);
        $cities = City::allCached();
        $positions = GoodsPosition::applySearchFilters($request)
            ->has('package')  // фикс потенциального бага, когда удаляют товар, но моменталки остаются
            ->leftJoin('goods', 'goods_positions.good_id', '=', 'goods.id')
            ->leftJoin('employees', 'goods_positions.employee_id', '=', 'employees.id')
            ->leftJoin('users', 'employees.user_id', '=', 'users.id')
            ->leftJoin('goods_packages', 'goods_positions.package_id', '=', 'goods_packages.id')
            ->select([
                'goods_positions.*', 'goods.title as good_title', 'goods.id as good_id', 'users.username',
                'goods_packages.city_id'
            ])
            ->where('goods_positions.available', '=', 0)
            ->where('goods_positions.moderated', '=', 0)
            ->orderBy('goods_positions.updated_at', 'ASC')
            ->paginate(20);

        if ($city = City::find($request->get('city_id'))) {
            $regions = $city->regions;
        }

        return view('shop.management.goods.moderate.index', [
            'positions' => $positions,
            'cities' => $cities,
            'regions' => $regions
        ]);
    }

    public function moderationAccept(Request $request, $positionId)
    {
        $this->authorize('management-sections-moderate');
        if ($request->get('_token') !== csrf_token()) {
            throw new TokenMismatchException;
        }

        /** @var GoodsPosition $position */
        $position = GoodsPosition::where('available', false)->where('moderated', false)->findOrFail($positionId);
        $this->performModeration($position, true);
        return redirect('/shop/management/goods/moderation')->with('flash_success', 'Квест отправлен на витрину.');
    }

    public function moderationDecline(Request $request, $positionId)
    {
        $this->authorize('management-sections-moderate');
        if ($request->get('_token') !== csrf_token()) {
            throw new TokenMismatchException;
        }

        /** @var GoodsPosition $position */
        $position = GoodsPosition::where('available', false)->where('moderated', false)->findOrFail($positionId);
        $this->performModeration($position, false);
        return redirect('/shop/management/goods/moderation')->with('flash_success', 'Квест удален.');
    }

    public function batchModeration(Request $request)
    {
        $this->authorize('management-sections-moderate');

        if (empty($request->get('positions'))) {
            return redirect('/shop/management/goods/moderation')->with('flash_warning', 'Вы не выбрали ни одного квеста.');
        }

        $selectedPositions = GoodsPosition::where('available', 0)
            ->where('moderated', 0)
            ->whereIn('id', $request->get('positions'))
            ->get();

        if ($request->has('accept')) {
            foreach ($selectedPositions as $position) {
                $this->performModeration($position, true);
            }
            return redirect('/shop/management/goods/moderation')->with('flash_success', 'Квесты добавлены на витрину.');
        } elseif ($request->has('decline')) {
            foreach ($selectedPositions as $position) {
                $this->performModeration($position, false);
            }
            return redirect('/shop/management/goods/moderation')->with('flash_success', 'Квесты удалены.');
        } else {
            return redirect('/shop/management/goods/moderation')->with('flash_warning', 'Неизвестное действие.');
        }
    }

    private function performModeration(GoodsPosition $position, $isAccepted)
    {
        $good = $position->good()->firstOrFail();

        $action = $isAccepted ? EmployeesLog::ACTION_QUESTS_MODERATE_ACCEPT : EmployeesLog::ACTION_QUESTS_MODERATE_DECLINE;
        EmployeesLog::log(\Auth::user(), $action,
            ['good_id' => $good->id, 'package_id' => $position->package_id, 'position_id' => $position->id],
            ['position' => $position, 'good_title' => $good->title]);

        if ($isAccepted) {
            $position->available = true;
            $position->moderated = true;
            $position->save();
            event(new PositionCreated($position));
        } else {
            $position->delete();
            // PositionDeleted dispatched inside delete() method
        }
    }
}
