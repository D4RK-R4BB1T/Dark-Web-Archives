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
            <form class="form-horizontal" action="{{ url('/shop/management/system/payments/employees/confirm') }}" role="form" method="get" enctype="multipart/form-data">
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
                            <span class="text-muted">Доступно дополнительных сотрудников:</span>
                        </div>
                        <div class="col-xs-12 col-sm-4 col-md-9 col-lg-12">
                            {{ $shop->employees_count }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-20 col-md-15 col-lg-12">
                            <span class="text-muted">Ежемесячные затраты на доп. сотрудников:</span>
                        </div>
                        <div class="col-xs-12 col-sm-4 col-md-9 col-lg-12">
                            {{ human_price($shop->getAdditionalEmployeePrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_USD) * $shop->employees_count, \App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }}
                        </div>
                    </div>

                    <hr class="small" />
                    <div class="form-group{{ $errors->has('employees_count') ? ' has-error' : '' }}">
                        <div class="col-xs-14 col-md-16 col-xs-offset-5 col-md-offset-4">
                            <span class="text-muted">Укажите нужное количество сотрудников:</span><br />
                            <input id="employees_count" name="employees_count" type="text" class="form-control" value="{{ old('employees_count') ?: $shop->employees_count }}" placeholder="Введите количество сотрудников" {{ autofocus_on_desktop() }} />
                            @if ($errors->has('employees_count'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('employees_count') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Оплатить</button>
                        &nbsp;
                        <a class="text-muted" href="{{ url("/shop/management/system/payments") }}">вернуться назад</a>
                    </div>
                </div>
            </form>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-system-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection