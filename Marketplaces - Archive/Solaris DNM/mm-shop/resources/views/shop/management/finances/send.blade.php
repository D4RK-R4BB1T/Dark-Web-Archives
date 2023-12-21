{{--
This file is part of MM2-dev project.
Description: Finances wallets send page
--}}
@extends('layouts.master')

@section('title', 'Отправить деньги :: Финансы')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.finances.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Перевод средств с кошелька: {{ $wallet->title }}</h3>
                <hr class="small" />
                <div class="text-center">
                    <h4>
                        Доступный баланс кошелька: {{ $wallet->getHumanRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}
                        <span style="font-size: 15px; position: relative; top: 1px" class="hint--top" aria-label="{{ $wallet->getHumanRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}, {{ $wallet->getHumanRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }}">
                            <i class="glyphicon glyphicon-question-sign" style="margin-top: 3px"></i>
                        </span>
                    </h4>
                </div>
                <hr class="small" />
                <form action="" method="post" class="form-horizontal">
                    {{ csrf_field() }}
                    <div class="form-group{{ $errors->has('amount') ? ' has-error' : '' }}">
                        <div class="col-md-16 col-md-offset-4">
                            <input id="amount" name="amount" type="text" class="form-control" value="{{ old('amount') }}" placeholder="Введите нужную сумму в BTC" />
                            @if ($errors->has('amount'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('amount') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group{{ $errors->has('wallet') ? ' has-error' : '' }}">
                        <div class="col-md-16 col-md-offset-4">
                            <input id="wallet" name="wallet" type="text" class="form-control" value="{{ old('wallet') }}" placeholder="Введите сюда биткоин-адрес, чтобы отправить BTC" />
                            @if ($errors->has('wallet'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('wallet') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 0">
                        <div class="col-md-16 col-md-offset-4 text-left">
                            <p class="text-muted" style="margin-bottom: 0">
                                @if (\App\Packages\Utils\BitcoinUtils::isPaymentsEnabled())
                                    Максимальная сумма с учетом комиссии - {{ human_price(max(0, $wallet->getRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) - config('mm2.bitcoin_fee')), \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}
                                    Минимальная сумма для вывода - {{ human_price(config('mm2.bitcoin_min'), \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }} <br />
                                    Максимальная сумма на одну транзакцию - {{ human_price(config('mm2.bitcoin_max'), \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }} <br />
                                    Комиссия - {{ human_price(config('mm2.bitcoin_fee'), \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">&nbsp;
                        @if($wallet->type === \App\Wallet::TYPE_PRIMARY && $wallet->shop_id === $shop->id && is_null($propertiesProvider->getBool(\App\Providers\DynamicPropertiesProvider::KEY_WDRAW_SHOP_WALLET)))
                            <button type="submit" class="btn btn-orange">Перевести деньги</button>
                        @else
                            <button type="submit" class="btn btn-orange" disabled>Перевести деньги</button>
                        @endif
                        &nbsp;
                        <a class="text-muted" href="{{ URL::previous() }}">вернуться назад</a>
                    </div>
                </form>
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-finances-reminder')
        </div> <!-- /.col-sm-6 -->

    </div> <!-- /.row -->
@endsection