{{--
This file is part of MM2-dev project.
Description: Shop management system page
--}}
@extends('layouts.master')

@section('title', 'Дополнительные сотрудники :: Оплата :: Системные настройки')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.system.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <form class="form-horizontal" action="{{ url('/shop/management/system/payments/employees/pay') }}" role="form" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <input type="hidden" name="employees_count" value="{{ $newEmployeesCount }}" />
                <div class="well block">
                    <h3>Дополнительные сотрудники</h3>
                    <hr class="small" />
                    <div class="row">
                        <div class="col-xs-12 col-sm-20 col-md-15 col-lg-12">
                            <span class="text-muted">Доступно сотрудников по тарифу:</span>
                        </div>
                        <div class="col-xs-12 col-sm-4 col-md-9">
                            {{ $shop->getPlanAvailableEmployeesCount() }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-20 col-md-15 col-lg-12">
                            <span class="text-muted">Количество дополнительных сотрудников:</span>
                        </div>
                        <div class="col-xs-12 col-sm-4 col-md-9 col-lg-12">
                            {{ $newEmployeesCount }}
                        </div>
                    </div>
                    <hr class="small" />
                    @if ($employeesCountDiff > 0) {{-- Need to show payment form --}}
                        <p>Для подключения дополнительных сотрудников до истечения срока оплаты ({{ $shop->expires_at->format('d.m.Y') }}) необходимо оплатить {{ human_price($employeesPrice, \App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }}.</p>
                        <hr class="small" />
                        <div class="form-group">
                            <div class="col-xs-14 col-md-16 col-xs-offset-5 col-md-offset-4">
                                <span class="text-muted">Сумма к оплате:</span><br />
                                <div class="input-group">
                                    <input type="text" class="form-control" value="{{ round_price($employeesPrice, \App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }}" readonly />
                                    <div class="input-group-addon">$</div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group has-feedback {{ $errors->has('wallet') ? ' has-error' : '' }}">
                            <div class="col-xs-14 col-md-16 col-xs-offset-5 col-md-offset-4">
                                <select name="wallet" class="form-control" title="Кошелек">
                                    <option value="">Кошелек</option>
                                    @foreach ($wallets as $wallet)
                                        <option value="{{ $wallet->id }}" {{ old('wallet') == $wallet->id ? 'selected' : '' }}>{{ $wallet->title }} ({{ $wallet->getHumanRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }})</option>
                                    @endforeach
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                @if ($errors->has('wallet'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('wallet') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    @else
                        <p>Число дополнительных сотрудников будет уменьшено на {{ abs($employeesCountDiff) }}, ежемесячная оплата будет уменьшена на {{ human_price($shop->getAdditionalEmployeePrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_USD) * abs($employeesCountDiff), \App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }}.</p>
                    @endif
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Оплатить</button>
                        &nbsp;
                        <a class="text-muted" href="{{ url("/shop/management/system/payments/employees") }}">вернуться назад</a>
                    </div>
                </div>
            </form>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-system-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection