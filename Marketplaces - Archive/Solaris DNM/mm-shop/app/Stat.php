<?php

namespace App;

use App\Packages\Highcharts;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Stat
 *
 * @property integer $id
 * @property string $date
 * @property integer $visitors_count
 * @property integer $orders_count
 * @method static \Illuminate\Database\Query\Builder|\App\Stat whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Stat whereDate($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Stat whereVisitorsCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Stat whereOrdersCount($value)
 * @mixin \Eloquent
 * @property string $visitors_data
 * @method static \Illuminate\Database\Query\Builder|\App\Stat whereVisitorsData($value)
 */
class Stat extends Model
{
    protected $table = 'stats';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'date', 'visitors_count', 'visitors_data', 'orders_count'
    ];

    protected $casts = [
        'visitors_data' => 'array'
    ];

    public static $visitorsChart = [
        'infile' => [
            'title' => [
                'text' => 'Статистика посетителей по дням'
            ],
            'xAxis' => [
                'type' => 'datetime',
                'tickInterval' => 1000 * 60 * 60 * 24, // 1000 ms * 60 secs * 60 mins * 24 hours = 1 day
                'dateTimeLabelFormats' => [
                    'day' => '%d %b'
                ],
                'gridLineWidth' => '1'
            ],
            'yAxis' => [
                'title' => [
                    'text' => NULL
                ],
                'floor' => 0,
                'tickAmount' => 11,
                'minTickInterval' => 1,
                'startOnTick' => false
            ],
            'legend' => [
                'align' => 'center',
                'verticalAlign' => 'bottom',
                'itemStyle' => [
                    'font' => '12px Segoe UI'
                ]
            ],
            'plotOptions' => [
                'series' => [
                    'pointStart' => 0, // here should be timestamp of the first day of the graph,
                    'pointInterval' => 1000 * 60 * 60 * 24, // 1000 ms * 60 secs * 60 mins * 24 hours = 1 day
                ]
            ],
            'series' => [[
                'name' => 'Количество пользователей',
                'data' => [] // here should be array of data, one item is for one day
            ]]
        ],
        // i don't know why highcharts accepts json only as string here
        'globalOptions' => '{"global":{"timezoneOffset":-180},"chart":{"style":{"fontFamily":"Segoe UI","fontSize":"18px"}},"lang":{"shortMonths":["янв","фев","мар","апр","май","июн","июл","авг","сен","окт","ноя","дек"],"numericSymbols":[" тыс.", " млн."]}}',
        'width' => '800'
    ];

    public static $ordersChart = [
        'infile' => [
            'title' => [
                'text' => 'Статистика покупок по дням'
            ],
            'xAxis' => [
                'type' => 'datetime',
                'tickInterval' => 1000 * 60 * 60 * 24, // 1000 ms * 60 secs * 60 mins * 24 hours = 1 day
                'dateTimeLabelFormats' => [
                    'day' => '%d %b'
                ],
                'gridLineWidth' => '1'
            ],
            'yAxis' => [
                'title' => [
                    'text' => NULL
                ],
                'floor' => 0,
                'tickAmount' => 11,
                'minTickInterval' => 1,
                'startOnTick' => false,
                'tickInterval' => 1
            ],
            'legend' => [
                'align' => 'center',
                'verticalAlign' => 'bottom',
                'itemStyle' => [
                    'font' => '12px Segoe UI'
                ]
            ],
            'plotOptions' => [
                'series' => [
                    'pointStart' => 0, // here should be timestamp of the first day of the graph,
                    'pointInterval' => 1000 * 60 * 60 * 24, // 1000 ms * 60 secs * 60 mins * 24 hours = 1 day
                ]
            ],
            'series' => [[
                'name' => 'Количество покупок',
                'data' => [] // here should be array of data, one item is for one day
            ]]
        ],
        // i don't know why highcharts accepts json only as string here
        'globalOptions' => '{"global":{"timezoneOffset":-180},"chart":{"style":{"fontFamily":"Segoe UI","fontSize":"18px"}},"lang":{"shortMonths":["янв","фев","мар","апр","май","июн","июл","авг","сен","окт","ноя","дек"],"numericSymbols":[" тыс.", " млн."]}}',
        'width' => '800'
    ];


    public static function getVisitorsCacheKey($date = null) {
        if ($date == null) {
            $date = Carbon::today();
        }

        return sprintf('%d-%d-%d_visitors', $date->day, $date->month, $date->year);
    }

    public static function generateGraphs() {
        /** @var Highcharts $highcharts */
        $highcharts = resolve('App\Packages\Highcharts');
        $startDate = Carbon::yesterday()->startOfDay()->addDays(-6);
        $endDate = Carbon::yesterday()->endOfDay();

        $visitors = [];
        $orders = [];

        for ($date = clone $startDate; $date <= $endDate; $date->addDay())
        {
            /** @var \App\Stat $stat */
            $stat = Stat::where('date', $date)->first();

            $visitorsCount = $stat ? $stat->visitors_count : 0;
            $ordersCount = $stat ? $stat->orders_count : 0;

            $visitors[] = $visitorsCount;
            $orders[] = $ordersCount;
        }

        $visitorsChart = Stat::$visitorsChart;
        $visitorsChart['infile']['plotOptions']['series']['pointStart'] = $startDate->timestamp * 1000;
        $visitorsChart['infile']['series'][0]['data'] = $visitors;
        $visitorsChartImage = $highcharts->drawGraph($visitorsChart);
        // sha1 used for valid ascii name
        // app key used for unique url for each shop
        $visitorsChartPath = 'charts/' . sha1('visitors' . $startDate . config('app.key')) . '.png';

        $ordersChart = Stat::$ordersChart;
        $ordersChart['infile']['plotOptions']['series']['pointStart'] = $startDate->timestamp * 1000;
        $ordersChart['infile']['series'][0]['data'] = $orders;
        $ordersChartImage = $highcharts->drawGraph($ordersChart);
        // sha1 used for valid ascii name
        // crypt used for unique url for each shop
        $ordersChartPath = 'charts/' . sha1('orders' . $startDate . config('app.key')) . '.png';

        $disk = \Storage::disk('public');
        $disk->put($visitorsChartPath, $visitorsChartImage);
        $disk->put($ordersChartPath, $ordersChartImage);

        $shop = Shop::getDefaultShop();
        $shop->visitors_chart_url = $disk->url($visitorsChartPath);
        $shop->orders_chart_url = $disk->url($ordersChartPath);
        $shop->save();
    }
}