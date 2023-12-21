{{--
This file is part of MM2-dev project.
Description: Finances all employees page
--}}
@extends('layouts.master')

@section('title', 'Общая статистика :: Финансы')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.finances.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Общая статистика сотрудников</h3>
                <hr class="small" />
                <div class="row">
                    <div class="col-xs-19 col-sm-18">
                        <span class="text-muted">Загружено квестов:</span>
                        <span class="text-danger">{{ $count = \App\GoodsPosition::whereIn('good_id', $shop->goods()->pluck('id'))->count() }}</span> {{ plural($count, ['квест', 'квеста', 'квестов']) }} (<span class="text-danger">{{ $shop->orders()->sum('status_was_problem') }}</span>&nbsp;проблемных)
                    </div>
                </div>
                <hr class="small" />
                <div class="text-center">&nbsp;
                    @if ($earnings !== null)
                        <a href="?show=payouts" class="text-muted">История выплат</a>
                    @else
                        <a href="?show=earnings" class="text-muted">История заработка</a>
                    @endif
                </div>
            </div> <!-- /.row -->

            @if ($earnings !== null)
                <div class="well block">
                    <h3 class="one-line">История заработка сотрудников</h3>
                    <hr class="small" />
                    @if ($earnings->count() > 0)
                        <table class="table table-header" style="margin-bottom: 0;">
                            <thead>
                            <tr>
                                <td>Сумма</td>
                                <td>Время</td>
                                <td>Сотрудник</td>
                                <td>Описание</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($earnings as $earning)
                                <tr>
                                    <td style="">
                                        @if($earning->amount > 0)
                                            <span class="text-success" style="position: relative; top: 1px"><i class="glyphicon glyphicon-plus-sign"></i></span>&nbsp;{{ human_price($earning->amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}
                                        @else
                                            <span class="text-danger" style="position: relative; top: 1px;"><i class="glyphicon glyphicon-minus-sign"></i></span>&nbsp;{{ human_price(-$earning->amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}
                                        @endif
                                    </td>
                                    <td>{{ $earning->created_at->format('d.m.Y в H:i') }}</td>
                                    <td>@if($employee = traverse($earning, 'employee')){{ $employee->getPrivateName() }}@else - @endif</td>
                                    <td style="text-overflow: ellipsis; word-wrap: break-word;">{{ $earning->description }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @if ($earnings->total() > $earnings->perPage())
                            <hr class="small" />
                            <div class="text-center">
                                {{ $earnings->appends(request()->input())->links() }}
                            </div>
                        @endif
                    @else
                        <div class="alert alert-info" style="margin-bottom: 0">Начислений не найдено</div>
                    @endif
                </div>
            @endif

            @if ($payouts !== null)
                <div class="well block">
                    <h3 class="one-line">История выплат сотрудникам</h3>
                    <hr class="small" />
                    @if ($payouts->count() > 0)
                        <table class="table table-header" style="margin-bottom: 0;">
                            <thead>
                            <tr>
                                <td>Сумма</td>
                                <td>Время</td>
                                <td>Кому выплачено</td>
                                <td>Кем выплачено</td>
                                <td>Описание</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($payouts as $payout)
                                <tr>
                                    <td style="">
                                        @if($payout->operation->amount > 0)
                                            <span class="text-success" style="position: relative; top: 1px"><i class="glyphicon glyphicon-plus-sign"></i></span>&nbsp;{{ human_price($payout->operation->amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}
                                        @else
                                            <span class="text-danger" style="position: relative; top: 1px;"><i class="glyphicon glyphicon-minus-sign"></i></span>&nbsp;{{ human_price(-$payout->operation->amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}
                                        @endif
                                    </td>
                                    <td>{{ $payout->operation->created_at->format('d.m.Y в H:i') }}</td>
                                    <td>@if($employee = traverse($payout, 'employee')){{ $employee->getPrivateName() }}@else - @endif</td>
                                    <td>@if($senderEmployee = traverse($payout, 'employee')){{ $senderEmployee->getPrivateName() }}@else - @endif</td>
                                    <td style="text-overflow: ellipsis; word-wrap: break-word;">{{ $payout->description }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @if ($payouts->total() > $payouts->perPage())
                            <hr class="small" />
                            <div class="text-center">
                                {{ $payouts->appends(request()->input())->links() }}
                            </div>
                        @endif
                    @else
                        <div class="alert alert-info" style="margin-bottom: 0">Выплат не найдено</div>
                    @endif
                </div>
            @endif
        </div> <!-- /.col-sm-12 -->
    </div> <!-- /.row -->
@endsection