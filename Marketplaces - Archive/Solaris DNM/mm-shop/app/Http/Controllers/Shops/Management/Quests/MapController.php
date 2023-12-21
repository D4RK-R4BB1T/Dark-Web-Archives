<?php

namespace App\Http\Controllers\Shops\Management\Quests;

use App\Employee;
use App\Good;
use App\GoodsPosition;
use App\Http\Controllers\Shops\Management\ManagementController;
use App\Packages\Utils\Formatters;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

/*
 * Карта квестов
 */
class MapController extends ManagementController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function($request, $next) {
            if (!$this->shop->integrations_quests_map) {
                abort(404);
            }
            $this->authorize('management-quests-map');
            return $next($request);
        });

        View::share('page', 'goods');
    }

    /**
     * Показать карту кладов
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $minutes = 1;
        list ($data, $count, $skipped) = Cache::remember('quests_map', $minutes, function () {
            return $this->getData();
        });

        return view('shop.management.goods.quests.map', [
            'data' => $data,
            'count' => $count,
            'skipped' => $skipped
        ]);
    }

    /**
     * @return array
     */
    private function getData()
    {
        list ($quests, $count, $skipped) = $this->getQuests();
        $goods = $this->getGoods($quests);

        $data = rawurlencode(json_encode([
            'quests' => $quests,
            'goods' => $goods
        ]));

        return [ $data, $count, $skipped ];
    }

    /**
     * @return array
     */
    private function getQuests()
    {
        $skipped = 0;
        $quests = GoodsPosition::where('available', 1)
            ->join('goods_packages', 'goods_packages.id', '=', 'goods_positions.package_id')
            ->select([
                'goods_positions.id',
                'goods_positions.good_id',
                'goods_positions.quest',
                'goods_packages.amount',
                'goods_packages.measure'
            ])->get();

        $count = $quests->count();

        foreach ($quests as $key => $quest) {
            $quest->geo = $this->parseGeo($quest->quest);
            if (!$quest->geo) {
                $quests->offsetUnset($key);
                $skipped++;
                continue;
            }

            $quest->amount = Formatters::getHumanWeight($quest->amount, $quest->measure);

            // Этого не должно быть в json
            unset($quest->measure);
        }

        $quests = $quests->values();

        return [ $quests, $count, $skipped ];
    }

    /**
     * @param Collection $quests
     * @return Collection
     */
    private function getGoods($quests)
    {
        $ids = $quests->pluck('good_id')->unique()->toArray();

        return Good::select('id', 'title')->findMany($ids);
    }

    /**
     * @param $str
     * @return array|null
     */
    private function parseGeo($str)
    {
        $pattern = '/-?(\d{1,4}[\.|,]\d{1,10})[, ?| ](\d{1,4}[\.|,]\d{1,10})/';
        if (!preg_match_all($pattern, $str, $matches, PREG_SET_ORDER)) {
            return null;
        }

        foreach ($matches as $match) {
            $lat = floatval($match[1]);
            $lng = floatval($match[2]);
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                continue;
            }

            // Рандомизация координат, очень приблизительно + или - 100-200м
            $div = 100000;
            $lat_r = mt_rand(0.00125 * $div, 0.0023 * $div) / $div;
            $lng_r = mt_rand(0.001 * $div, 0.002 * $div) / $div;

            $lat = mt_rand(0, 1) ? $lat + $lat_r : $lat - $lat_r;
            $lng = mt_rand(0, 1) ? $lng + $lng_r : $lng - $lng_r;

            return [ $lat, $lng ];
        }

        return null;
    }
}
