{{--
This file is part of MM2-catalog project.
Description: Shops page
--}}
<?php

$hasExchangeAmount = request()->has('exchange_amount');
$exchangeAmount = old('amount') ?: floatval(request()->get('exchange_amount', 1000));
?>
@extends('layouts.master')

@section('title', __('layout.Balance'))
@section('header_scripts')
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
                const $payoutAddress = document.querySelector('#payoutWallet');
                const $payoutSum = document.querySelector('#payoutSum');

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
@endsection
@section('content')
    <div class="row">
        <div class="col-sm-18 col-md-18 col-lg-18 animated fadeIn">
            <div class="well block">
                <h3>Курсы обмена BTC</h3>
                <hr class="small"/>
                <div class="text-center">
                    <span style="font-size: 21px;">
                        {{ number_format($exchange['rub'], 0, '.', ' ') }} RUB
                    </span>
                    </br>
                    <span style="font-size: 21px;">
                        {{ number_format($exchange['usd'], 0, '.', ' ') }} USD
                    </span>
                    <hr class="small">
                </div>
            </div>
            <div class="well block">
                <h3>Ваш кошелек</h3>
                <hr class="small"/>
                <div class="text-center">
                    <span style="font-size: 21px;">
                        Баланс вашего кошелька: {{ $balance }}
                    </span>
                    <hr class="small">
                </div>
            </div>
            <div class="well block">
                <h3>Пополнить баланс</h3>
                <hr class="small"/>
                <div class="text-center">
                    <div style="height: 40px;">
                    <label for="wallet" style="font-weight: normal; position: relative; top: 8px" class="col-xs-12 col-xs-offset-1 text-right text-muted">Используйте указанный биткоин-адрес, чтобы получить BTC:</label>
                    <div class="col-xs-9">
                        <p style="margin: 5px; font-weight: bold">{{ $payment_address }}</p>
                    </div>
                    </div>
                    <hr class="small">
                </div>
            </div>
                <div class="well block">
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
                            <form action="{{ url('/balance/exchange') }}" method="post" role="form">
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
                            <form action="{{ url('/exchange_confirmation') }}" method="post" role="form">
                                {{ csrf_field() }}
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
                                <a class="text-muted" href="{{ URL::previous() }}">Назад</a>
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
            <div class="well block">
                <h3>История</h3>
                @if (count($operations) == 0)
                    <hr class="small"/>
                    <div class="alert alert-info" style="margin-bottom: 0">Транзакций еще не было.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>Описание</td>
                                <td class="col-xs-5">Сумма</td>
                                <td class="col-xs-6">Время</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($operations['result'] as $operation)
                                <tr>
                                    <td>
                                        {{ $operation['info'] }}
                                        @if(in_array($operation['name'], ['queue_withdraw', 'withdraw']))
                                            (<a href="https://www.blockchain.com/btc/address/{{ $operation['data']['address'] }}">посмотреть операцию</a>)
                                        @endif
                                        @if(isset($operation['data']['note']) && !$operation['data']['note'] == '')
                                            ({{ $operation['data']['note'] }})
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($operation['amount']))
                                            @php echo sprintf("%.8f", $operation['amount']) ?? '' @endphp
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($operation['date']))
                                            {{ \Carbon\Carbon::parse($operation['date'])->format('d.m.Y в H:i') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($operations['pages'])
                        <hr class="small" />
                        <div class="text-center">
                            <ul class="pagination">
                                <!-- Previous Page Link -->
                                @if($current_page > 1)
                                    <li class="page-item"><a class="page-link" href="?page={{ $current_page - 1 }}" rel="next">«</a></li>
                                @else
                                    <li class="page-item disabled"><span class="page-link">«</span></li>
                                @endif

                                <!-- Pagination Elements -->
                                <!-- "Three Dots" Separator -->

                                <!-- Array Of Links -->
                                @php
                                    $show = 0;
                                    $dots = 0;
                                    @endphp
                                @for($i = 1; $i <= $operations['pages']; $i++)
                                    @php $show++; @endphp
                                    @if($i === $current_page)
                                        <li class="page-item active"><span class="page-link">{{ $i }}</span></li>
                                    @elseif($show < 10  || ($operations['pages'] == $i))
                                        <li class="page-item"><a class="page-link" href="?page={{ $i }}">{{ $i }}</a></li>
                                    @elseif(!$dots)
                                        @php $dots = 1; @endphp
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    @endif
                                @endfor

                                <!-- Next Page Link -->
                                @if($current_page < $operations['pages'])
                                    <li class="page-item"><a class="page-link" href="?page={{ $current_page + 1 }}" rel="next">»</a></li>
                                @else
                                    <li class="page-item disabled"><span class="page-link">»</span></li>
                                @endif
                            </ul>
                        </div>
                    @endif
                @endif
            </div>

        </div><!-- /.col-sm-13 -->
        <div class="col-sm-6 col-md-6 col-lg-6">
            @include('balance.components.block-balance-reminder')
        </div>
    </div>
@endsection

@section('modals')
    @include('balance.components.modals.balance-qrcode')
@endsection