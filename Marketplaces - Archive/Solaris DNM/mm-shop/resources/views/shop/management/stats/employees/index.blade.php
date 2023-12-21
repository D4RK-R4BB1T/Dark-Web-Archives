{{--
This file is part of MM2-dev project.
Description: Shop management employees stats page
--}}
@extends('layouts.master')

@section('title', 'Сотрудники :: Статистика')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.stats.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            @include('shop.management.stats.components.component-employees-filter')
            <div class="well block">
                @if($employee->count() < 1)
                    <div class="alert alert-info" style="margin-bottom: 0">Выберите сотрудника.</div>
                @elseif($stats->count() == 0)
                    <div class="alert alert-info" style="margin-bottom: 0">Статистики за данный период не найдено.</div>
                @else
                    <h3>Общая статистика</h3>
                    <hr class="small" />
                    <p></p>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Квестов в модерации:</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                            {{ $stats['positions_not_moderated_count'] }}
                        </div>
                    </div>

                    <p></p>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Квестов на витрине:</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                            {{ $stats['positions_added_count'] }}
                        </div>
                    </div>

                    <p></p>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Количество штрафов:</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                            {{ $stats['employee_penalties_count'] }}
                        </div>
                    </div>
                    <p></p>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Сумма покупок:</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                        {{
                            (collect($stats['shop_earn'])->filter(function($value) {
                                return $value !== 0; // remove empty currencies
                            })
                            ->map(function($value, $currency) {
                                return human_price($value, $currency); // convert to human format
                            })
                            ->implode(', ')) ?: '0'
                        }}
                        </div>
                    </div>
                    <h3>Статистика по проданным позициям</h3>
                    <hr class="small" />
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>Товар</td>
                                <td>Загружено</td>
                                <td>
                                    <span class="hint--top" aria-label="Оплата за выбранный промежуток времени за все клады, что добавил курьер в магазин">
                                        Опл. сделанных <i class="glyphicon glyphicon-question-sign cursor-pointer text-info"></i>
                                    </span>
                                </td>
                                <td>
                                    <span class="hint--top" aria-label="Клады, сделка по которым была завершена успешно">
                                        Купленные <i class="glyphicon glyphicon-question-sign cursor-pointer text-info"></i>
                                    </span>
                                </td>
                                <td>
                                    <span class="hint--top" aria-label="Оплата за выбранный промежуток времени за все проданные клады">
                                        Опл. проданных  <i class="glyphicon glyphicon-question-sign cursor-pointer text-info"></i>
                                    </span>
                                </td>
                                <td>
                                    <span class="hint--top" aria-label="Штрафы по кладам курьера за выбранный промежуток времени">
                                        Штрафы <i class="glyphicon glyphicon-question-sign cursor-pointer text-info"></i>
                                    </span>
                                </td>
                                <td>Проблемных</td>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['goods_stats'] as $package_id => $goods)
                                    <tr>
                                        <td>
                                            <a href="{{ url('/shop/management/goods/' . $goods['id']) }}">{{ $goods['title'] }} {{ $goods['amount'] }}</a>
                                        </td>
                                        <td>
                                            {{ $goods['positions_added_count'] }}
                                        </td>
                                        <td>
                                            {{ (collect($goods['employee_earn_positions'])->filter(function($value) {
                                                        return $value !== 0; // remove empty currencies
                                                    })
                                                    ->map(function($value, $currency) {
                                                        return human_price($value, $currency); // convert to human format
                                                    })
                                                    ->implode(', ')) ?: human_price(0, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}
                                        </td>
                                        <td>
                                            {{ $goods['positions_sell_count'] }}
                                        </td>
                                        <td>
                                            {{ (collect($goods['employee_earn_orders'])->filter(function($value) {
                                                    return $value !== 0; // remove empty currencies
                                                })
                                                ->map(function($value, $currency) {
                                                    return human_price($value, $currency); // convert to human format
                                                })
                                                ->implode(', ')) ?: human_price(0, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}
                                        </td>
                                        <td>
                                        {{
                                            (collect($goods['employee_penalties_sum'])->filter(function($value) {
                                                return $value !== 0; // remove empty currencies
                                            })
                                            ->map(function($value, $currency) {
                                                return human_price($value, $currency); // convert to human format
                                            })
                                            ->implode(', ')) ?: human_price(0, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB)
                                        }}
                                        </td>
                                        <td>
                                            {{ $goods['employee_disputes_count'] }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="bg-default">
                                    <td>Всего</td>
                                    <td>
                                        {{ $stats['positions_added_count'] }}
                                    </td>
                                    <td>
                                        {{ (collect($stats['employee_earn_positions'])->filter(function($value) {
                                                return $value !== 0; // remove empty currencies
                                            })
                                            ->map(function($value, $currency) {
                                                return human_price($value, $currency); // convert to human format
                                            })
                                            ->implode(', ')) ?: human_price(0, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}
                                    </td>
                                    <td>
                                        {{ $stats['positions_sell_count'] }}
                                    </td>
                                    <td>
                                        {{ (collect($stats['employee_earn_orders'])->filter(function($value) {
                                            return $value !== 0; // remove empty currencies
                                        })
                                        ->map(function($value, $currency) {
                                            return human_price($value, $currency); // convert to human format
                                        })
                                        ->implode(', ')) ?: human_price(0, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}
                                    </td>
                                    <td>
                                        {{ human_price($stats['employee_penalties_sum'][\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB], \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}
                                    </td>
                                    <td>
                                        {{ $stats['employee_disputes_count'] }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div> <!-- /.col-sm-18 -->
    </div> <!-- /.row -->
@endsection
