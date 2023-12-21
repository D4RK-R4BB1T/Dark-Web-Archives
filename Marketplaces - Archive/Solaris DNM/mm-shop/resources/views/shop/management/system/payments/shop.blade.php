{{--
This file is part of MM2-dev project.
Description: Shop management system page
--}}
@extends('layouts.master')

@section('title', 'Магазин :: Оплата :: Системные настройки')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.system.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <form class="form-horizontal" action="{{ url('/shop/management/system/payments/shop/pay') }}" role="form" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="well block">
                    <h3>Оплата магазина</h3>
                    <hr class="small" />
                    <div class="row">
                        <div class="col-xs-12 col-sm-20 col-md-15 col-lg-12">
                            <span class="text-muted">Новый срок оплаты магазина:</span>
                        </div>
                        <div class="col-xs-12 col-sm-4 col-md-9">
                            {{ (($shop->expires_at > \Carbon\Carbon::now()) ? $shop->expires_at : \Carbon\Carbon::now())->addMonth()->format('d.m.Y')  }}
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="form-group">
                        <div class="col-xs-14 col-md-16 col-xs-offset-5 col-md-offset-4">
                            <span class="text-muted">Сумма к оплате:</span><br />
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ round_price($shop->getTotalPlanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB), \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}" readonly />
                                <div class="input-group-addon">₽</div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group has-feedback {{ $errors->has('wallet') ? ' has-error' : '' }}">
                        <div class="col-xs-14 col-md-16 col-xs-offset-5 col-md-offset-4">
                            <select name="wallet" class="form-control" title="Кошелек">
                                <option value="">Кошелек</option>
                                @foreach ($wallets as $wallet)
                                    <option value="{{ $wallet->id }}" {{ old('wallet') == $wallet->id ? 'selected' : '' }}>{{ $wallet->title }} ({{ $wallet->getHumanRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }})</option>
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