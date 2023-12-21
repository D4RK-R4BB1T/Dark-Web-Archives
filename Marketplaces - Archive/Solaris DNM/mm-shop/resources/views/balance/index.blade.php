{{--
This file is part of MM2-dev project.
Description: Balance page
--}}

<?php
use App\Packages\Utils\BitcoinUtils;

$isNewExchangeEnabled = config('mm2.exchanges_only_beta') === false || Auth::user()->isBetaUser();

if (BitcoinUtils::isPaymentsEnabled()) {
    $rubRate = BitcoinUtils::convert(1, BitcoinUtils::CURRENCY_BTC, BitcoinUtils::CURRENCY_RUB);
    $usdRate = BitcoinUtils::convert(1, BitcoinUtils::CURRENCY_BTC, BitcoinUtils::CURRENCY_USD);
} else {
    $rubRate = null;
    $usdRate = null;
}

$hasExchangeAmount = request()->has('exchange_amount');
$exchangeAmount = old('amount') ?: floatval(request()->get('exchange_amount', 1000));
?>

@extends('layouts.master')

@section('title', 'Баланс')

@section('header_scripts')
    @if ($isNewExchangeEnabled)
    <style>
        .large-font {
            font-family: inherit;
            font-size: 21px;
            line-height: 1.1;
        }

        .exchange-input {
            font-family: inherit;
            font-size: 21px;
            line-height: 1.1;
            background: none;
            border: 0 dashed;
            border-bottom-width: 1px;
            outline: none;
            text-decoration: dashed;
            -webkit-appearance: none;
            -moz-appearance: none;
            border-radius: 0;
            padding: 0;
            margin: 0;
            width: auto;
        }

        #exchangeTip {
            display: none;
            font-size: 21px;
            position: relative;
            top: -1px;
        }
    </style>
    <script type="text/javascript">
        const rates = {'rub': {{ $rubRate ?? 0 }}, 'usd': {{ $usdRate ?? 0 }}, 'btc': 1};
        const convert = (amount, from, to) => {
            const decimals = to === 'btc' ? 6 : 2;
            return parseFloat((amount * rates[to] / rates[from]).toFixed(decimals));
        }
        document.addEventListener('DOMContentLoaded', () => {
            if (rates['rub'] <= 0 || rates['usd'] <= 0) { return; }

            const $exchangeTip = document.querySelector('#exchangeTip');
            const $amountInput = document.querySelector('#amountInput')
            const $currencySelect = document.querySelector('#currencySelect');
            const $fakeAmountInput = document.querySelector('#fakeAmountInput');
            const $fakeCurrencySelect = document.querySelector('#fakeCurrencySelect');

            const getState = () => {
                const $currency = $currencySelect.value;
                const $amount = parseFloat($amountInput.value);
                return {
                    "amount": $amount,
                    "currency": $currency,
                    "btc": convert($amount, $currency, 'btc'),
                    "rub": convert($amount, $currency, 'rub'),
                    "usd": convert($amount, $currency, 'usd')
                }
            }
            const updateTooltip = () => {
                let tooltipText;
                const state = getState();
                if (state.currency === 'usd') {
                    tooltipText = `${state.btc} BTC, ${state.rub} ₽`
                } else if (state.currency === 'rub') {
                    tooltipText = `${state.btc} BTC, ${state.usd} $`
                } else if (state.currency === 'btc') {
                    tooltipText = `${state.rub} ₽, ${state.usd} $`
                }
                $exchangeTip.setAttribute('aria-label', tooltipText);
            }

            const amountChanged = () => {
                $amountInput.value = $amountInput.value.replace(/[^0-9.,]/g, '').replace(',', '.').replace(/(\..*)\./g, '$1');
                $fakeAmountInput.innerText = $amountInput.value;
                $amountInput.style.width = $fakeAmountInput.getBoundingClientRect().width + 'px';
                updateTooltip();
            }

            const currencyChanged = () => {
                const $option = $currencySelect.options[$currencySelect.selectedIndex];
                $fakeCurrencySelect[0].innerText = $option.innerText;
                $currencySelect.style.width = $fakeCurrencySelect.getBoundingClientRect().width + 'px';
                updateTooltip();
            }

            $exchangeTip.style.display = 'inline-block';
            $amountInput.addEventListener('input', amountChanged);
            $amountInput.addEventListener('change', amountChanged);
            $currencySelect.addEventListener('change', currencyChanged);
            $amountInput.dispatchEvent(new Event('change'));
            $currencySelect.dispatchEvent(new Event('change'));
        }, false)
    </script>
    @endif
@endsection
@section('content')
    <div class="row">
        <div class="col-sm-18 col-md-18 col-lg-18 animated fadeIn">
            <div class="well block">
                <h3>Ваш кошелек</h3>
                <hr class="small"/>
                <div class="text-center">
                    <span style="font-size: 21px;">
                        Баланс вашего кошелька: {{ Auth::user()->getHumanRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}
                    </span>
                    <span style="font-size: 21px; position: relative; top: -1px" class="hint--top" aria-label="{{ Auth::user()->getHumanRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}, {{ Auth::user()->getHumanRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }}">
                        <i class="glyphicon glyphicon-question-sign" style="font-size: 16px"></i>
                    </span>
                    @if (\App\Packages\Utils\BitcoinUtils::isPaymentsEnabled())
                        <span style="font-size: 21px; position: relative; top: -1px" class="hint--top" aria-label="Посмотреть QR-код кошелька">
                            @component('layouts.components.component-modal-toggle', ['id' => 'balance-qrcode'])
                                <i class="glyphicon glyphicon-qrcode" style="font-size: 16px"></i>
                            @endcomponent
                        </span>
                    @endif
                    @if (($pendingBalance = Auth::user()->getPendingBalance()) > 0)
                        <br />
                        <span class="text-muted" style="font-size: 19px;">
                            (+{{ human_price($pendingBalance, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }} ожидает подтверждения)
                        </span>
                    @endif
                    {{--<br />--}}
                    {{--<button type="button" class="btn btn-orange btn-borderless" style="position: relative; top: -4px;">--}}
                        {{--<strong>Купить BTC</strong>--}}
                    {{--</button>--}}
                    <hr class="small">
                    <form class="form-horizontal" method="post" role="form" action="{{ url('/balance') }}">
                        {{ csrf_field() }}
                        <input type="hidden" name="action" value="wallet">
                        <div class="form-group">
                            @if($request->get('action') !== 'wallet')
                                <button class="btn btn-orange" type="submit">Показать адрес BTC</button>
                                <span class="help-block">
                                    <strong class="text-danger">Каждый раз перед платежом уточняйте адрес кошелька в магазине. Он может быть изменен. Будьте внимательны.</strong>
                                </span>
                            @else
                                <label for="wallet" style="font-weight: normal; position: relative; top: 8px" class="col-xs-12 col-xs-offset-1 text-right text-muted">Используйте указанный биткоин-адрес, чтобы получить BTC:</label>
                                <div class="col-xs-9">
                                    <input id="wallet" type="text" class="form-control" value="{{ Auth::user()->primaryWallet()->segwit_wallet }}" />
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            @if ($isNewExchangeEnabled)
            <div class="well block @if($hasExchangeAmount) animated pulse @endif">
                <h3>Обмен валют</h3>
                <hr class="small" />
                @if ($errors->count() > 0)
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                    <hr class="small" />
                @endif
                <div class="text-center">
                    @if (!isset($btcAmount))
                        <form action="" method="post" role="form">
                            {{ csrf_field() }}
                            <input type="hidden" name="action" value="exchange">
                            <select class="exchange-input" id="fakeCurrencySelect" style="visibility: hidden; position: absolute;"><option></option></select>
                            <span class="exchange-input" id="fakeAmountInput" style="visibility: hidden; position: absolute;"></span>
                            <span class="large-font">
                                Пополнить баланс на
                                <span class="hint--top" aria-label="Нажмите для изменения суммы">
                                    <input inputmode="numeric" name="amount" type="text" id="amountInput" class="exchange-input" value="{{ $exchangeAmount }}" size="4">
                                </span>
                                <span class="hint--top" aria-label="Нажмите для изменения валюты">
                                    <select id="currencySelect" name="currency" class="exchange-input" style="cursor: pointer">
                                    <option value="rub" {{ old('currency') == 'rub' ? 'selected' : ''}}>₽</option>
                                    <option value="usd" {{ old('currency') == 'usd' ? 'selected' : ''}}>$</option>
                                    <option value="btc" {{ old('currency') == 'btc' ? 'selected' : ''}}>BTC</option>
                                </select>
                                </span>
                                <span id="exchangeTip" class="hint--top" aria-label="">
                                    <i class="glyphicon glyphicon-question-sign" style="font-size: 16px"></i>
                                </span>
                            </span>
                            <hr class="small" />
                            <span class="text-muted">
                                Для изменения суммы и валюты нажмите на их значения.<br />
                                Обмен будет осуществлен с помощью проверенных обменников Solaris.
                            </span>
                            <br />
                            <hr class="small" />
                            <button class="btn btn-orange" type="submit">Продолжить</button>
                        </form>
                    @else
                        <form action="" method="post" target="_blank" role="form">
                            {{ csrf_field() }}
                            <input type="hidden" name="action" value="exchange_confirmation">
                            <input type="hidden" name="currency" value="{{ $currency }}">
                            <input type="hidden" name="amount" value="{{ $amount }}">

                            <span class="large-font">
                                На ваш баланс будет зачислено: {{ human_price($btcAmount, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}
                            </span>
                            <hr class="small" />
                            <span class="text-muted">
                                После нажатия на кнопку "Продолжить" будет создана заявка на обмен. <br />
                                Повторная заявка не сможет быть создана ранее, чем через 15 минут.
                            </span>
                            <br />
                            <hr class="small" />
                            <button class="btn btn-orange" type="submit">Продолжить</button>
                            &nbsp;
                            <a class="text-muted" href="{{ URL::previous() }}">вернуться назад</a>
                        </form>
                    @endif
                </div>

                @if ($exchanges->count() > 0)
                    <hr class="small" />
                    <h4>Недавние заявки на обмен</h4>
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>ID заявки</td>
                                <td>Сумма</td>
                                <td>Время</td>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($exchanges as $exchange)
                                <tr>
                                    <td><code>{{ $exchange->payment_id }}</code></td>
                                    <td>
                                        <span class="hint--top dashed" aria-label="{{ $exchange->getHumanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }}, {{ $exchange->getHumanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}">
                                            {{ $exchange->getHumanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}
                                        </span>
                                    </td>
                                    <td>{{ $exchange->created_at->format('d.m.Y в H:i') }}</td>
                                    <td>
                                        <a target="_blank" rel="noopener noreferer" class="dark-link hint--top" aria-label="Перейти к заявке" href="{{ url('/balance/redirect/' . $exchange->payment_id) }}">
                                            <i class="glyphicon glyphicon-circle-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            @else
            <div class="well block">
                <h3>Обмен валют</h3>
                <hr class="small" />
                <div class="alert alert-warning">
                    Уважаемые пользователи, ведутся работы над интеграцией обменников. <br />
                    Вскоре Вам будет доступен обмен по направлениям QIWI / Карты банков / Сим-карты / Яндекс.Деньги и другие электронные активы. <br />
                    На данный момент вы можете воспользоваться сторонними обменниками от наших партнеров, которые не станут задавать лишние вопросы: <br />
                    <i class="glyphicon glyphicon-bitcoin"></i>&nbsp;<b>БиткоинБанк:</b> <a target="_blank" rel="noopener noreferer" href="http://momentwpfug2jsfakwj74wp4zok5p2ub2secsw5kn4arj2nl5noxw5ad.onion/"><b>http://momentwpfug2jsfakwj74wp4zok5p2ub2secsw5kn4arj2nl5noxw5ad.onion</b></a><br />
                    <i class="glyphicon glyphicon-bitcoin"></i>&nbsp;<b>VipCoin:</b> <a target="_blank" rel="noopener noreferer" href="http://vipcoinnpouvzaa7rtltye55rosd5ihe7giwxgqkimueuvkw5aqm2dyd.onion/5zazm"><b>http://vipcoinnpouvzaa7rtltye55rosd5ihe7giwxgqkimueuvkw5aqm2dyd.onion</b></a><br />
                    <i class="glyphicon glyphicon-bitcoin"></i>&nbsp;<b>✅ СБЕР - ОФИЦИАЛЬНЫЙ ОБМЕННИК:</b> <a target="_blank" rel="noopener noreferer" href="https://btc-obmen.cc"><b>https://btc-obmen.cc</b></a><br />
                    <i class="glyphicon glyphicon-bitcoin"></i>&nbsp;<b>СБЕРБАНК: </b><a target="_blank" rel="noopener noreferer" href="http://sberbkuhvyffmkljwdvuvhkir2b7v5da4mckasl5pus7pk23prjputqd.onion"><b>http://sberbkuhvyffmkljwdvuvhkir2b7v5da4mckasl5pus7pk23prjputqd.onion</b></a><br />
                    <i class="glyphicon glyphicon-bitcoin"></i>&nbsp;<b>БИТКОИН ЗА МИНУТУ 24/7 - ОБМЕН/ОБНАЛ/ЧИСТКА: </b><a target="_blank" rel="noopener noreferer" href="https://bitminute24-7.com"><b>https://bitminute24-7.com</b></a>
                </div>
            </div>
            @endif
            <div class="well block">
                <h3>Вывод средств</h3>
                <hr class="small"/>
                <div class="text-center">
                    <form class="form-horizontal" role="form" method="post">
                        {{ csrf_field() }}
                        <input type="hidden" name="action" value="payout">
                        <div class="form-group {{ $errors->has('amount') ? ' has-error' : '' }}">
                            <div class="col-md-13 col-md-offset-4">
                                <input id="amount" name="amount" type="text" class="form-control" placeholder="Введите нужную сумму в BTC" value="{{ old('amount') }}" />
                                @if ($errors->has('amount'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('amount') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group {{ $errors->has('wallet') ? ' has-error' : '' }}">
                            <div class="col-md-13 col-md-offset-4">
                                <input id="wallet" name="wallet" type="text" class="form-control" placeholder="Введите сюда биткоин-адрес, чтобы отправить BTC" value="{{ old('wallet') }}"/>
                                @if ($errors->has('wallet'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('wallet') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-orange" style="height: 32px"><strong>Вывести деньги</strong></button>
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 0">
                            <div class="col-md-13 col-md-offset-4 text-left">
                                <p class="text-muted" style="margin-bottom: 0">
                                    @if (\App\Packages\Utils\BitcoinUtils::isPaymentsEnabled())
                                        Максимальная сумма с учетом комиссии - {{ human_price(max(0, Auth::user()->primaryWallet()->getRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) - config('mm2.bitcoin_fee')), \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }} <br />
                                        Минимальная сумма для вывода - {{ human_price(config('mm2.bitcoin_min'), \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }} <br />
                                        Максимальная сумма на одну транзакцию - {{ human_price(config('mm2.bitcoin_max'), \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }} <br />
                                        Комиссия - {{ human_price(config('mm2.bitcoin_fee'), \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="well block">
                <h3>История</h3>
                @if ($operations->count() == 0)
                    <hr class="small"/>
                    <div class="alert alert-info" style="margin-bottom: 0">Транзакций еще не было.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td class="col-xs-5">Сумма</td>
                                <td class="col-xs-6">Время</td>
                                <td>Описание</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($operations as $operation)
                                <tr>
                                    <td>
                                        @if($operation->amount > 0)
                                            <span class="text-success" style="position: relative; top: 1px"><i class="glyphicon glyphicon-plus-sign"></i></span>&nbsp;<span class="hint--top dashed" aria-label="{{ human_price(btc2usd($operation->amount), \App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }}, {{ human_price(btc2rub($operation->amount), \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}">{{ human_price($operation->amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}</span>
                                        @else
                                            <span class="text-danger" style="position: relative; top: 1px;"><i class="glyphicon glyphicon-minus-sign"></i></span>&nbsp;<span class="hint--top dashed" aria-label="{{ human_price(btc2usd(-$operation->amount), \App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }}, {{ human_price(btc2rub(-$operation->amount), \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}">{{ human_price(-$operation->amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $operation->created_at->format('d.m.Y в H:i') }}</td>
                                    <td>
                                        {{ $operation->description }}
                                        @if ($operation->order)
                                            <a class="dark-link" href="{{ url('/orders/'.$operation->order->id) }}"><i class="glyphicon glyphicon-eye-open"></i></a>
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
                @endif
            </div>

        </div><!-- /.col-sm-13 -->
        <div class="col-sm-6 col-md-6 col-lg-6">
            @include('balance.components.block-balance-reminder')
        </div> <!-- /.col-lg-5 -->
    </div> <!-- /.row -->
@endsection

@section('modals')
    @include('balance.components.modals.balance-qrcode')
@endsection