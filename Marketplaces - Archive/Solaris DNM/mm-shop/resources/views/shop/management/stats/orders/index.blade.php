{{--
This file is part of MM2-dev project.
Description: Shop management orders stats page
--}}
@extends('layouts.master')

@section('title', 'Покупки :: Статистика')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.stats.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            @include('shop.management.stats.components.component-orders-filter')
            <div class="well block">
                <h3>
                    Информация о покупках ({{ $periodStart->format('d.m.Y') }} - {{ $periodEnd->format('d.m.Y') }})
                </h3>
                <hr class="small" />
                <p>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Общее количество покупок:</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                            {{ $totalOrdersStats->count }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Общая сумма покупок (фиат):</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                            {{
                                (collect($totalOrdersStats->total)->filter(function($value) {
                                    return $value !== 0; // remove empty currencies
                                })
                                ->map(function($value, $currency) {
                                    return human_price($value, $currency); // convert to human format
                                })
                                ->implode(', ')) ?: '-'
                            }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Общая сумма покупок (BTC):</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                            <span class="hint--top dashed" aria-label="{{ human_price(btc2usd($totalOrdersStats->total_btc), \App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }}, {{ human_price(btc2rub($totalOrdersStats->total_btc), \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}">
                                {{ human_price($totalOrdersStats->total_btc, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}
                            </span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Средний процент проблемных:</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                            {{ round($totalOrdersStats->problems_avg, 2) }} %
                        </div>
                    </div>
                </p>
                @if (count($ordersStats) > 0)
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>Товар</td>
                                <td>Кол-во покупок</td>
                                <td>Объем проданного</td>
                                <td>Общая стоимость</td>
                                <td>% проблемных</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($ordersStats as $goodTitle => $row)
                                <tr>
                                    <td>
                                        @if($row->good)
                                            <a href="{{ url('/shop/'.$shop->slug.'/goods/'.$row->good_id) }}" target="_blank">{{ $goodTitle }}</a>
                                        @else
                                            <span class="hint--top" aria-label="Товар удален из магазина">{{ $goodTitle }}</span>
                                        @endif
                                        <br /><i class="glyphicon glyphicon-map-marker"></i> {{ $row->city->title }}
                                    </td>
                                    <td>
                                        @can('management-sections-orders')
                                            <a href="{{ url('/shop/management/orders?good='.$row->good_id) }}">{{ $row->count }} {{ plural($row->count, ['покупка', 'покупки', 'покупок']) }}</a>
                                        @else
                                            {{ $row->count }} {{ plural($row->count, ['покупка', 'покупки', 'покупок']) }}
                                        @endcan
                                    </td>
                                    <td>
                                        {{
                                            collect($row->measures)->filter(function($value) {
                                                return $value !== 0; // remove empty measures
                                            })
                                            ->map(function($amount, $measure) {
                                                return \App\Packages\Utils\Formatters::getHumanWeight($amount, $measure);
                                            })
                                            ->implode(', ')
                                        }}
                                    </td>
                                    <td>
                                        <span class="hint--top dashed" aria-label="Общая полученная сумма в BTC: {{ human_price($row->total_btc, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }} ({{ human_price(btc2rub($row->total_btc), \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }})">
                                        {{
                                            collect($row->total)->filter(function($value) {
                                                return $value !== 0; // remove empty currencies
                                            })
                                            ->map(function($value, $currency) {
                                                return human_price($value, $currency); // convert to human format
                                            })
                                            ->implode(', ')
                                        }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ round($row->problems_count / $row->count * 100, 2) }} %
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info" style="margin-bottom: 0">Нет информации за выбранный период</div>
                @endif
            </div>
            @if ($shop->orders_chart_url)
                <div class="well block">
                    <h3>Статистика покупок</h3>
                    <hr class="small" />
                    <img class="img-responsive" src="{{ url('$shop->orders_chart_url') }}" />
                    <hr class="small" />
                    <span class="text-muted">Статистика обновляется ежедневно в 00:00 по московскому времени.</span>
                </div>
            @endif
        </div> <!-- /.col-sm-18 -->
    </div> <!-- /.row -->
@endsection