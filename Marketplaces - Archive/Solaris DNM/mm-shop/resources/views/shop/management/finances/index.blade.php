{{--
This file is part of MM2-dev project.
Description: Finances wallets page
--}}
@extends('layouts.master')

@section('title', 'Кошельки :: Финансы')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.finances.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Кошельки</h3>
                <hr class="small" />
                <div class="table-responsive">
                <table class="table table-header" style="margin-bottom: 0; ">
                    <thead>
                    <tr>
                        <td class="col-xs-8 col-md-7 col-lg-8">Метка</td>
                        <td class="col-xs-7 col-md-9 col-lg-10">Адрес</td>
                        <td class="col-xs-5 col-md-4 col-lg-3">Баланс</td>
                        <td class="col-xs-4 col-md-4 col-lg-3"></td>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($shop->wallets()->get() as $wallet)
                        <tr>
                            <td>{{ $wallet->title }}</td>
                            <td style="word-wrap: break-word;">{{ $wallet->segwit_wallet }}</td>
                            <td>
                                <span class="hint--top dashed" aria-label="{{ $wallet->getHumanRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }}, {{ $wallet->getHumanRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}">{{ $wallet->getHumanRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}</span>
                                @if (($pendingBalance = $wallet->getPendingBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_BTC)) > 0)
                                    <span class="hint--top" aria-label="{{ human_price($pendingBalance, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }} ожидает подтверждения"><i class="glyphicon glyphicon-info-sign"></i></span>
                                @endif
                            </td>
                            <td class="text-right" style="font-size: 15px">
                                @if($wallet->type === \App\Wallet::TYPE_PRIMARY && $wallet->shop_id === $shop->id && !is_null($propertiesProvider->getBool(\App\Providers\DynamicPropertiesProvider::KEY_WDRAW_SHOP_WALLET)))
                                    <a class="dark-link hint--top" aria-label="Отправка запрещена службой безопасности" href="#"><i class="glyphicon glyphicon-send text-danger"></i></a>
                                @else
                                    <a class="dark-link hint--top" aria-label="Отправить деньги" href="{{ url('/shop/management/finances/send/'.$wallet->id) }}"><i class="glyphicon glyphicon-send"></i></a>
                                @endif
                                &nbsp;
                                <a class="dark-link hint--top" aria-label="История кошелька" href="{{ url('/shop/management/finances/view/'.$wallet->id) }}"><i class="glyphicon glyphicon-eye-open"></i></a>
                                &nbsp;
                                @if ($wallet->type !== \App\Wallet::TYPE_PRIMARY)
                                    <a class="dark-link hint--top" aria-label="Редактировать" href="{{ url('/shop/management/finances/edit/'.$wallet->id) }}"><i class="glyphicon glyphicon-pencil"></i></a>
                                @else
                                    <a class="text-muted hint--left" aria-label="Редактирование основного кошелька запрещено" href="#"><i class="glyphicon glyphicon-pencil"></i></a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            </div> <!-- /.row -->

            <div class="well block">
                <h3 class="one-line">История платежей всех кошельков</h3>
                <hr class="small" />
                @if ($operations->count() > 0)
                    <div class="table-responsive">
                    <table class="table table-header" style="margin-bottom: 0;">
                        <thead>
                        <tr>
                            <td class="col-xs-4 col-lg-4">Сумма</td>
                            <td class="col-xs-5 col-lg-5">Время</td>
                            <td class="col-xs-8 col-lg-8">Адрес</td>
                            <td class="col-xs-7 col-lg-5">Описание</td>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($operations as $operation)
                            <tr>
                                <td style="">
                                    @if($operation->amount > 0)
                                        <span class="text-success" style="position: relative; top: 1px"><i class="glyphicon glyphicon-plus-sign"></i></span>&nbsp;<span class="hint--top dashed" aria-label="{{ human_price(btc2usd($operation->amount), \App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }}, {{ human_price(btc2rub($operation->amount), \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}">{{ human_price($operation->amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}</span>
                                    @else
                                        <span class="text-danger" style="position: relative; top: 1px;"><i class="glyphicon glyphicon-minus-sign"></i></span>&nbsp;<span class="hint--top dashed" aria-label="{{ human_price(btc2usd(-$operation->amount), \App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }}, {{ human_price(btc2rub(-$operation->amount), \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}">{{ human_price(-$operation->amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}</span>
                                    @endif
                                </td>
                                <td>{{ $operation->created_at->format('d.m.Y в H:i') }}</td>
                                <td style="word-wrap: break-word">{{ $operation->trashedWallet->segwit_wallet }}</td>
                                <td style="text-overflow: ellipsis; word-wrap: break-word;">
                                    {{ $operation->description }}
                                    @if ($operation->order)
                                        <a class="dark-link" href="{{ url('/shop/management/orders/'.$operation->order->id) }}"><i class="glyphicon glyphicon-eye-open"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                    @if ($operations->total() > $operations->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $operations->links() }}
                        </div>
                    @endif
                @else
                    <div class="alert alert-info" style="margin-bottom: 0">Платежей не найдено</div>
                @endif
            </div>
        </div> <!-- /.col-sm-12 -->
        {{--<div class="col-sm-5 animated fadeIn">--}}
            {{--@include('shop.management.components.block-finances-reminder')--}}
        {{--</div> <!-- /.col-sm-6 -->--}}

    </div> <!-- /.row -->
@endsection