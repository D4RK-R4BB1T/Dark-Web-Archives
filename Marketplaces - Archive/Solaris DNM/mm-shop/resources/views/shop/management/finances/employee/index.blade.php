{{--
This file is part of MM2-dev project.
Description: Finances employee page
--}}
@extends('layouts.master')

@section('title', $employee->getPrivateName() . ' :: Финансы')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.finances.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Личный баланс сотрудника: {{ $employee->getPrivateName() }}</h3>
                <hr class="small" />
                <div class="row">
                    <div class="col-xs-19 col-sm-18">
                        <span class="text-muted">Загружено квестов:</span>
                        <span class="text-danger">{{ $count = $employee->positions()->count() }}</span> {{ plural($count, ['квест', 'квеста', 'квестов']) }} (<span class="text-danger">{{ $employee->orders()->sum('status_was_problem') }}</span>&nbsp;проблемных)
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-19 col-sm-18">
                        <span class="text-muted">Заработано денег:</span>
                        {{ $employee->getHumanBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}
                        @if (($payout = $employee->payouts()->orderBy('id', 'desc')->first()) && $payout->operation)
                            (с {{ $payout->operation->created_at->format('d.m.Y H:i') }})
                        @endif
                    </div>
                </div>
                <hr class="small" />
                <div class="text-center">
                    <a href="{{ url('/shop/management/finances/employee/payout/'.$employee->id) }}" class="btn btn-orange">Выплатить деньги</a>
                    &nbsp;
                    @if ($earnings !== null)
                        <a href="?show=payouts" class="text-muted">История выплат</a>
                    @else
                        <a href="?show=earnings" class="text-muted">История заработка</a>
                    @endif
                </div>
            </div> <!-- /.row -->

            @if ($earnings !== null)
                <div class="well block">
                    <h3 class="one-line">История заработка сотрудника</h3>
                    <hr class="small" />
                    @if ($earnings->count() > 0)
                        <table class="table table-header" style="margin-bottom: 0;">
                            <thead>
                            <tr>
                                <td class="col-xs-4 col-lg-4">Сумма</td>
                                <td class="col-xs-5 col-lg-5">Время</td>
                                <td class="col-xs-7 col-lg-5">Описание</td>
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
                    <h3 class="one-line">История выплат сотруднику</h3>
                    <hr class="small" />
                    @if ($payouts->count() > 0)
                        <table class="table table-header" style="margin-bottom: 0;">
                            <thead>
                            <tr>
                                <td class="col-xs-5 col-lg-5">Сумма</td>
                                <td class="col-xs-5 col-lg-5">Время</td>
                                <td class="col-xs-6 col-lg-5">Кем выплачено</td>
                                <td class="col-xs-8 col-lg-9">Описание</td>
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
                                    <td>{{ $payout->senderEmployee ? $payout->senderEmployee->getPrivateName() : '-' }}</td>
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

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-finances-employees-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection