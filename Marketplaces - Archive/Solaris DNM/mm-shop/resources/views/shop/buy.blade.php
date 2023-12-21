{{-- 
This file is part of MM2-dev project. 
Description: Buy confirmation page
--}}
@extends('layouts.master')

@section('title', 'Подтверждение заказа :: ' . $good->title)

@section('content')
    @include('layouts.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
        'breadcrumbs' =>
        [
            // BREADCRUMB_CATALOG,
            // BREADCRUMB_SHOPS,
            ['title' => $shop->title, 'url' => url('/shop/' . $shop->slug)],
            ['title' => $good->title, 'url' => url('/shop/' . $shop->slug . '/goods/' . $good->id)],
            ['title' => 'Подтверждение заказа']
        ]
    ])

    <div class="row">
        <div class="col-sm-6 col-md-6 col-lg-5">
            @include('shop.sidebar')
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-13 col-md-13 col-lg-13 animated fadeIn">
            @if($errors->has('balance'))
                <div class="alert alert-warning">Недостаточно средств на балансе.</div>
            @endif

            <form role="form" action="" method="post">
                {{ csrf_field() }}
                <div class="well block good-info">
                    <h3>Подтверждение заказа</h3>
                    <div class="table-responsive">
                    <table class="table table-header" style="margin-bottom: 0">
                        <thead>
                        <tr>
                            <td class="col-xs-7">Товар</td>
                            <td>Количество</td>
                            <td>Местоположение</td>
                            <td class="col-xs-7">Информация о доставке</td>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>{{ $good->title }}</td>
                            <td>{{ $package->getHumanWeight() }}</td>
                            <td>
                                {{ $package->city->title }}
                                @if ($position && $position->region)
                                    <br />{{ $position->region->title }}
                                @elseif($position && $position->customPlace)
                                    <br />{{ $position->customPlace->title }}
                                @endif
                            </td>
                            <td>
                                @if ($package->preorder)
                                    Доставка предзаказа в течение {{ $package->preorder_time }} часов
                                @else
                                    Моментальная покупка
                                @endif
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    </div>
                    <hr class="small" />
                    @if ($package->preorder && count($services = $package->services()) > 0)
                        @foreach ($services as $key => $service)
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="services[]" value="{{ $service->id }}" {{ old('services.' . $key) ? 'checked' : '' }}> {{ $service->title }} + {{ human_price($service->price, $service->currency) }}
                                </label>
                            </div>
                        @endforeach
                        <hr class="small" />
                    @endif
                    <div class="row">
                        <div class="col-md-11 col-lg-12">
                            <div class="row">
                                <div class="col-xs-19">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="promocode" placeholder="Промо-код" value="{{ old('promocode') }}" />
                                    </div>
                                </div>
                                <div class="col-xs-5">
                                    <div class="form-group">
                                        <button class="btn btn-orange" type="submit" name="apply_code" value="true">»</button>
                                    </div>
                                </div>
                            </div>
                            @if ($package->preorder)
                                <div class="form-group">
                                    <textarea rows="2" class="form-control" name="comment" placeholder="Примечание к заказу">{{ old('comment') }}</textarea>
                                </div>
                            @endif
                        </div>
                        <?php
                        $showGroupDiscount = Auth::user()->shouldShowGroupDiscount() && Auth::user()->group->percent_amount > 0;
                        ?>
                        <div class="col-md-13 col-lg-12">
                            <p>
                                <?php
                                /** @var \App\GoodsPackage $package */
                                $modifiers = [
                                    \App\Packages\PriceModifier\PriceModifierService::REFERRAL_MODIFIER
                                ];
                                if (!$showGroupDiscount) {
                                    $modifiers = [\App\Packages\PriceModifier\PriceModifierService::GROUP_MODIFIER] + $modifiers;
                                }
                                $arguments = ['user' => Auth::user()];
                                $btcPrice = $package->getHumanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_BTC, $modifiers, $arguments);
                                $rubPrice = $package->getHumanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB, $modifiers, $arguments);
                                $rubRawPrice = $package->getPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB, $modifiers, $arguments);
                                ?>
                                Стоимость: <span class="pull-right" style="{{ ($promocode || $showGroupDiscount) ? 'text-decoration: line-through' : '' }}">{{ $btcPrice  }} ({{ $rubPrice }})</span>
                            </p>
                            @if ($package->qiwi_enabled && !$shop->isExpired() && $wallet = $shop->qiwiWallets()->availableForPackage($package)->first())
                                <p>
                                    Стоимость (QIWI): <span class="pull-right" style="{{ ($promocode || $showGroupDiscount) ? 'text-decoration: line-through' : '' }}">{{ $package->getHumanQiwiPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB, [\App\Packages\PriceModifier\PriceModifierService::REFERRAL_MODIFIER]) }}</span>
                                </p>
                            @endif


                            @if ($promocode || $showGroupDiscount)
                                @if ($showGroupDiscount)
                                    <p>
                                        Ваша скидка: <span class="pull-right">{{ Auth::user()->group->getHumanDiscount() }}</span>
                                    </p>
                                @endif

                                @if ($promocode)
                                    <p>
                                        Промо-код: <span class="pull-right">{{ $promocode->getHumanDiscount() }}</span>
                                    </p>
                                @endif
                                <p>
                                    Со скидкой: <span class="pull-right">
                                        <?php
                                            /** @var \App\Promocode $promocode */
                                            /** @var \App\GoodsPackage $package */
                                            $modifiers = [
                                                \App\Packages\PriceModifier\PriceModifierService::PROMOCODE_MODIFIER,
                                                \App\Packages\PriceModifier\PriceModifierService::GROUP_MODIFIER,
                                                \App\Packages\PriceModifier\PriceModifierService::REFERRAL_MODIFIER
                                            ];
                                            $arguments = ['promocode' => $promocode, 'user' => Auth::user()];
                                            $btcPrice = $package->getHumanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_BTC, $modifiers, $arguments);
                                            $rubPrice = $package->getHumanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB, $modifiers, $arguments);
                                        ?>
                                        {{ $btcPrice  }} ({{ $rubPrice }})
                                    </span>
                                </p>
                            @endif
                                {{--<p>--}}
                                {{--С гарантом: <span class="pull-right">{{ $package->getHumanPriceWithGuaranteeFee(\App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }} ({{ $package->getHumanPriceWithGuaranteeFee(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }})</span>--}}
                            {{--</p>--}}
                            {{--<p>--}}
                                {{--Использовать гарант:--}}
                                {{--<span class="pull-right">--}}
                                    {{--<label class="radio-inline">--}}
                                        {{--<input type="radio" id="guarantee_1" name="guarantee" value="1" {{ old('guarantee') === '1' ? 'checked' : '' }}> да--}}
                                    {{--</label>--}}
                                    {{--<label class="radio-inline">--}}
                                        {{--<input type="radio" id="guarantee_0" name="guarantee" value="0" {{ old('guarantee') !== '1' ? 'checked' : '' }}> нет--}}
                                    {{--</label>--}}
                                {{--</span>--}}
                            {{--</p>--}}
                            <hr class="small separator" />
                            <div class="text-right">
                                <a class="btn btn-orange" href="{{ url('/balance?exchange_amount=' . $rubRawPrice) }}">Купить за фиат</a>
                                <button type="submit" class="btn btn-orange"><i class="glyphicon glyphicon-bitcoin"></i> Купить за BTC</button>
                                @if ($package->qiwi_enabled && isset($wallet))
                                    <button type="submit" class="btn btn-orange" name="qiwi" value="true"><i class="mmicon-qiwi-inversed"></i> Купить за QIWI</button>
                                @endif
                            </div>
                        </div>
                    </div> <!-- /.row -->
                </div> <!-- / .well -->
            </form>
        </div> <!-- /.col-lg-13 -->
        <div class="col-sm-5 col-md-5 col-lg-6 animated fadeIn">
            @if (!$package->preorder)
                @include('shop.components.block-buy-reminder')
            @else
                @include('shop.components.block-buy-preorder-reminder')
            @endif
        </div>
    </div> <!-- /.row -->
@endsection