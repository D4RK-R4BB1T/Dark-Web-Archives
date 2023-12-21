{{--
This file is part of MM2-dev project.
Description: Shop management orders stats page
--}}
@extends('layouts.master')

@section('title', 'Учет товаров :: Статистика')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.stats.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            @include('shop.management.stats.components.component-lots-filter')
            <div class="well block">
                <h3>Общее количество имеющихся товаров</h3>
                <hr class="small" />
                <p>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Всего партий:</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                            {{ $lots->count() }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Общий остаток:</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                            @if ($lots->count() > 0)
                            <span class="hint--top dashed" aria-label="{{
                                $lotsStats->map(function ($lotStat) {
                                    return $lotStat->good_title . ': ' . (collect($lotStat->measures)
                                        ->filter(function($value) {
                                            return $value !== 0; // remove empty measures
                                        })
                                        ->map(function($amount, $measure) {
                                            return \App\Packages\Utils\Formatters::getHumanWeight($amount, $measure);
                                        })
                                        ->implode(','));
                                })->implode('&#10;')
                            }}">
                            {{
                                $lotsTotalStats->filter(function($value) {
                                    return $value !== 0; // remove empty measures
                                })
                                ->map(function($amount, $measure) {
                                    return \App\Packages\Utils\Formatters::getHumanWeight($amount, $measure);
                                })
                                ->implode(', ')
                            }}
                            </span>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </p>
                <hr class="small" />
                <div class="text-center">
                    <a class="btn btn-orange" href="{{ url("/shop/management/stats/accounting/add") }}">Добавить партию</a>
                </div>
            </div>

            @if ($lots->count() > 0)
                <div class="well block">
                    <h3>Учет товаров</h3>
                    <hr class="small" />
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>Товар</td>
                                <td>Дата создания</td>
                                <td>Количество</td>
                                <td>Стоимость</td>
                                <td>Сотрудники</td>
                                <td>Остаток</td>
                                <td>Выручка</td>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($lots as $lot)
                                <tr>
                                    <td>
                                        @if ($lot->note)
                                            <span class="hint--top dashed" aria-label="{{ $lot->note }}">
                                        @endif
                                        {{ traverse($lot, 'good->title') ?: '-' }}
                                        @if ($lot->note)
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $lot->created_at->format('d.m в H:i') }}</td>
                                    <td>{{ $lot->getHumanTotalWeight() }}</td>
                                    <td>{{ $lot->getHumanTotalPrice() }}</td>
                                    <td>
                                        <span class="hint--top dashed" aria-label="{{
                                        ($lot->distributions)->map(function($distribution) {
                                            $str = '-';

                                            if($employee = traverse($distribution, 'employee')) {
                                                $str = $employee->getPrivateName();
                                            }

                                            return $str.': '.$distribution->getHumanTotalWeight();
                                        })->implode('&#10;')
                                    }}">{{ $lot->distributions->count() }}</span>
                                    </td>
                                    <td><span class="hint--top dashed" aria-label="{{
                                        ($lot->distributions)->map(function($distribution) {
                                            $str = '-';

                                            if($employee = traverse($distribution, 'employee')) {
                                                $str = $employee->getPrivateName();
                                            }

                                            return $str.': '.$distribution->getHumanAvailableWeight() . ' ост.';
                                        })->implode('&#10;')
                                    }}">{{ $lot->getHumanAvailableWeight() }}</span></td>
                                    <td>
                                        {{
                                            human_price(
                                                \App\Packages\Utils\BitcoinUtils::convert(
                                                    $lot->distributions->sum('proceed_btc'),
                                                    \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC,
                                                    $lot->currency),
                                                $lot->currency)
                                        }}
                                    </td>
                                    <td class="text-right">
                                        <a class="dark-link hint--top" aria-label="Распределение товара" href="{{ url('/shop/management/stats/accounting/'.$lot->id) }}"><i class="glyphicon glyphicon-user"></i></a>
                                        &nbsp;
                                        <a class="dark-link hint--top" aria-label="Редактировать" href="{{ url('/shop/management/stats/accounting/edit/'.$lot->id) }}"><i class="glyphicon glyphicon-edit"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($lots->total() > $lots->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $lots->appends(request()->input())->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div> <!-- /.col-sm-18 -->
    </div> <!-- /.row -->
@endsection