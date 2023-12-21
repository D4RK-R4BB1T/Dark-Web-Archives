@extends('layouts.master')

@section('title', 'Обмен')

@section('content')
    @include('layouts.components.sections-menu')

    <div class="row">
        <div class="col-xs-24 col-sm-18 col-md-18 col-lg-19 pull-right animated fadeIn">
            <div class="well block">
                <h3>Новый обмен</h3>
                <hr class="small" />
                <p class="text-muted">
                    Магазин обслуживается обменником <strong>{{ $exchange->title }}</strong>. <br />
                    <pre>{{ $exchange->description }}</pre>
                </p>
                <hr class="small" />
                <form action="" method="post" class="form-horizontal">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label class="col-xs-10 col-md-8 control-label">Курс обмена:</label>
                        <div class="col-xs-14 col-md-16">
                            <p class="form-control-static">1 BTC = {{ human_price($exchange->btc_rub_rate, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-10 col-md-8 control-label">Текущий резерв обменника:</label>
                        <div class="col-xs-14 col-md-16">
                            <p class="form-control-static dashed hint--top" aria-label="{{ $exchange->exchangeWallet()->getHumanRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}, {{ $exchange->exchangeWallet()->getHumanRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }}">
                                {{ $exchange->exchangeWallet()->getHumanRealBalance() }}
                            </p>
                        </div>
                    </div>
                    <div class="form-group {{ $errors->has('currency') ? ' has-error' : '' }}">
                        <label class="col-xs-10 col-md-8 control-label">Сумма обмена:</label>
                        <div class="col-xs-14 col-md-16 col-lg-8">
                            <div class="radio">
                                <label>
                                    <input type="radio" name="currency" value="btc" {{ (!old('currency') || old('currency') === 'btc') ? 'checked' : '' }}>
                                    Укажите сумму в BTC:
                                    <?php
                                    $btcBalance = $exchange->exchangeWallet()->getRealBalance() * (1 - config('mm2.exchange_api_fee'));
                                    $minExchangeAmountBtc = $exchange->convertRubles($exchange->min_amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC);
                                    $maxExchangeAmountBtc = $exchange->convertRubles($exchange->max_amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC);

                                    $minExchangeAmountBtc = min($btcBalance, $minExchangeAmountBtc);
                                    $maxExchangeAmountBtc = min($btcBalance, $maxExchangeAmountBtc);
                                    ?>
                                    <input type="number" step="any" min="{{ $minExchangeAmountBtc }}" max="{{ $maxExchangeAmountBtc }}" pattern="[0-9\.]+" name="amount_btc" class="form-control" value="" placeholder="0.">
                                    @if ($errors->has('amount_btc'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('amount_btc') }}</strong>
                                        </span>
                                    @else
                                        <span class="help-block">Минимум: {{ human_price($minExchangeAmountBtc, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}, максимум: {{ human_price($maxExchangeAmountBtc, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}</span>
                                    @endif
                                </label>
                            </div>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="currency" value="rub" {{ (old('currency') === 'rub') ? 'checked' : '' }}>
                                    Укажите сумму в рублях:
                                    <?php
                                    $btcBalance = $exchange->exchangeWallet()->getRealBalance() * (1 - config('mm2.exchange_api_fee'));
                                    $rubBalance = $exchange->convertRubles($btcBalance, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB);

                                    $minExchangeAmountRub = min($rubBalance, $exchange->min_amount);
                                    $maxExchangeAmountRub = min($rubBalance, $exchange->max_amount);
                                    ?>
                                    <input type="number" step="any" min="{{ $minExchangeAmountRub }}" max="{{ $maxExchangeAmountRub }}" pattern="[0-9\.]+" name="amount_rub" class="form-control" value="">
                                    @if ($errors->has('amount_btc'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('amount_rub') }}</strong>
                                        </span>
                                    @else
                                        <span class="help-block">Минимум: {{ human_price($minExchangeAmountRub, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}, максимум: {{ human_price($maxExchangeAmountRub, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}</span>
                                    @endif
                                </label>
                            </div>

                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <span class="text-muted">
                            Указанный выше курс может незначительно измениться за время обработки заявки. <br />
                            Обмен осуществляется не администрацией магазина, а сторонним обменником. <br />
                            Нажимая кнопку "Создать заявку", вы соглашаетесь с правилами обмена, указанными выше.
                        </span>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Создать заявку</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-xs-24 col-sm-6 col-md-6 col-lg-5 pull-left">
            @include('exchange.sidebar')
        </div> <!-- /.col-lg-5 -->
    </div> <!-- /.row -->
@endsection