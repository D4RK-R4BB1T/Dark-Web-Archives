{{--
This file is part of MM2-dev project.
Description: Main page of the shop
--}}
@extends('layouts.master')

@section('title', 'Добавление упаковки')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => "{$good->title} ($city->title)", 'url' => url('/shop/management/goods/packages/' . $good->id)],
        ['title' => 'Упаковки', 'url' => url('/shop/management/goods/packages/city/' . $good->id . '/' . $city->id)],
        ['title' => 'Добавление упаковки']
    ]])
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <form action="" role="form" method="post">
                {{ csrf_field() }}
                @for ($i = 0; $i < $pakCount; $i++)
                    <div class="well block">
                        <h3>Добавление упаковки: {{ $good->title }} ({{ $city->title }})</h3>
                        <hr class="small" />
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="form-group{{ $errors->has("packages.$i.amount") ? ' has-error' : '' }}">
                                    <input id="amount" type="text" class="form-control" name="packages[{{ $i }}][amount]" placeholder="Количество" value="{{ old("packages.$i.amount") }}" {{ $i == 0 ? autofocus_on_desktop() : '' }}>
                                    @if ($errors->has("packages.$i.amount"))
                                        <span class="help-block">
                                            <strong>{{ $errors->first("packages.$i.amount") }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div> <!-- /.col-xs-12 -->
                            <div class="col-xs-12">
                                <div class="form-group has-feedback{{ $errors->has("packages.$i.measure") ? ' has-error' : '' }}">
                                    <select name="packages[{{ $i }}][measure]" class="form-control" title="Единица измерения">
                                        <option value="">Единица измерения</option>
                                        <option value="{{ \App\GoodsPackage::MEASURE_GRAM }}" {{ old("packages.$i.measure") == \App\GoodsPackage::MEASURE_GRAM ? 'selected' : '' }}>г.</option>
                                        <option value="{{ \App\GoodsPackage::MEASURE_PIECE }}" {{ old("packages.$i.measure") == \App\GoodsPackage::MEASURE_PIECE ? 'selected' : '' }}>шт.</option>
                                        <option value="{{ \App\GoodsPackage::MEASURE_ML }}" {{ old("packages.$i.measure") == \App\GoodsPackage::MEASURE_ML ? 'selected' : '' }}>мл.</option>
                                    </select>
                                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>

                                    @if ($errors->has("packages.$i.measure"))
                                        <span class="help-block">
                                            <strong>{{ $errors->first("packages.$i.measure") }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div> <!-- /.col-xs-12 -->
                        </div> <!-- /.row -->
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="form-group{{ $errors->has("packages.$i.price") ? ' has-error' : '' }}">
                                    <input id="price" type="text" class="form-control" name="packages[{{ $i }}][price]" placeholder="Стоимость" value="{{ old("packages.$i.price") }}">

                                    @if ($errors->has("packages.$i.price"))
                                        <span class="help-block">
                                            <strong>{{ $errors->first("packages.$i.price") }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div> <!-- /.col-xs-12 -->
                            <div class="col-xs-12">
                                <div class="form-group has-feedback{{ $errors->has("packages.$i.currency") ? ' has-error' : '' }}">
                                    <select name="packages[{{ $i }}][currency]" class="form-control" title="Валюта">
                                        <option value="">Валюта</option>
                                        <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB }}" {{ old("packages.$i.currency") == \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB ? 'selected' : '' }}>Рубль (RUB)</option>
                                        <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC }}" {{ old("packages.$i.currency") == \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC ? 'selected' : '' }}>Bitcoin (BTC)</option>
                                        <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_USD }}" {{ old("packages.$i.currency") == \App\Packages\Utils\BitcoinUtils::CURRENCY_USD ? 'selected' : '' }}>Доллар (USD)</option>
                                    </select>
                                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>

                                    @if ($errors->has("packages.$i.currency"))
                                        <span class="help-block">
                                            <strong>{{ $errors->first("packages.$i.currency") }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div> <!-- /.col-xs-12 -->
                        </div> <!-- /.row -->
                        {{--<hr class="small" />
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="checkbox" style="margin-top: 1px">
                                    <label>
                                        <input type="checkbox" name="qiwi_enabled" value="true" {{ old('qiwi_enabled') == true ? 'checked' : ''}}> Упаковку можно купить за QIWI
                                    </label>
                                </div>
                            </div> <!-- /.col-xs-12 -->
                        </div> <!-- /.row -->
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="form-group{{ $errors->has('qiwi_price') ? ' has-error' : '' }}">
                                    <input id="qiwi_price" type="text" class="form-control" name="qiwi_price" placeholder="Стоимость при покупке через QIWI" value="{{ old('qiwi_price') }}">

                                    @if ($errors->has('qiwi_price'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('qiwi_price') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div> <!-- /.col-xs-12 -->
                            <div class="col-xs-12">
                                <div class="form-group has-feedback">
                                    <select class="form-control" title="Валюта" disabled>
                                        <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB }}" selected>Рубль (RUB)</option>
                                    </select>
                                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                </div>
                            </div> <!-- /.col-xs-12 -->
                        </div> <!-- /.row -->
                        <div class="row">
                            <div class="col-xs-24">
                                <span class="help-block" style="margin-top: 0">Если вы не хотите указывать другую цену при покупке за QIWI, оставьте это поле пустым.</span>
                            </div>
                        </div>
                        --}}
                        <hr class="small" />
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="checkbox" style="margin-top: 1px">
                                    <label>
                                        <input type="checkbox" name="packages[{{ $i }}][preorder]" value="true" {{ old("packages.$i.preorder") ? 'checked' : ''}}> Предзаказ
                                    </label>
                                </div>
                            </div> <!-- /.col-xs-12 -->
                            <div class="col-xs-12">
                                <span class="help-block" style="margin-top: 0">Работает только при режиме "Предзаказ".</span>
                            </div>
                        </div> <!-- /.row -->
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="form-group has-feedback{{ $errors->has("packages.$i.preorder_time") ? ' has-error' : '' }}">
                                    <select name="packages[{{ $i }}][preorder_time]" class="form-control" title="Время">
                                        <option value="">Время</option>
                                        <option value="{{ \App\GoodsPackage::PREORDER_TIME_24 }}" {{ old("packages.$i.preorder_time") == \App\GoodsPackage::PREORDER_TIME_24 ? 'selected' : '' }}>24 часа</option>
                                        <option value="{{ \App\GoodsPackage::PREORDER_TIME_48 }}" {{ old("packages.$i.preorder_time") == \App\GoodsPackage::PREORDER_TIME_48 ? 'selected' : '' }}>48 часов</option>
                                        <option value="{{ \App\GoodsPackage::PREORDER_TIME_72 }}" {{ old("packages.$i.preorder_time") == \App\GoodsPackage::PREORDER_TIME_72 ? 'selected' : '' }}>72 часа</option>
                                        <option value="{{ \App\GoodsPackage::PREORDER_TIME_480 }}" {{ old("packages.$i.preorder_time") == \App\GoodsPackage::PREORDER_TIME_480 ? 'selected' : '' }}>20 дней</option>
                                    </select>
                                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                    @if ($errors->has("packages.$i.preorder_time"))
                                        <span class="help-block">
                                            <strong>{{ $errors->first("packages.$i.preorder_time") }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-xs-12">
                                <div class="dropdown form-group">
                                    <input type="text" class="form-control" value="Платные услуги" readonly />
                                    <ul class="dropdown-menu orange" role="menu">
                                        @if (count($services) > 0)
                                            @foreach ($services as $service)
                                                <li role="presentation">
                                                    <div class="checkbox dropdown-checkbox">
                                                        <label>
                                                            <input type="checkbox" name="packages[{{ $i }}][services][]" value="{{ $service->id }}"> {{ $service->title }} - {{ $service->getHumanPrice() }}
                                                        </label>
                                                    </div>
                                                </li>
                                            @endforeach
                                        @else
                                            <li role="presentation">
                                                <div style="padding: 5px 10px">Нет доступных услуг.</div>
                                            </li>
                                        @endif
                                    </ul>
                                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                </div>
                            </div>
                        </div> <!-- /.col-xs-12 -->
                        <hr class="small" />
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="form-group{{ $errors->has("packages.$i.employee_reward") ? ' has-error' : '' }}">
                                    <input id="employee_reward" type="text" class="form-control" name="packages[{{ $i }}][employee_reward]" placeholder="Выплата работнику (руб.)" value="{{ old("packages.$i.employee_reward") }}">

                                    @if ($errors->has("packages.$i.employee_reward"))
                                        <span class="help-block">
                                            <strong>{{ $errors->first("packages.$i.employee_reward") }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-xs-12">
                                <div class="form-group{{ $errors->has("packages.$i.employee_penalty") ? ' has-error' : '' }}">
                                    <input id="employee_penalty" type="text" class="form-control" name="packages[{{ $i }}][employee_penalty]" placeholder="Штраф работнику (руб.)" value="{{ old("packages.$i.employee_penalty") }}">

                                    @if ($errors->has("packages.$i.employee_penalty"))
                                        <span class="help-block">
                                            <strong>{{ $errors->first("packages.$i.employee_penalty") }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-24">
                                <span class="help-block" style="margin-top: 0;">
                                    Вы можете указать сумму выплаты за проданный квест и штраф за проблемный заказ. При изменении значений происходит пересчет всех заложенных (но не проданных) квестов.
                                    Штраф может быть как равным выплате, так и больше ее.
                                </span>
                            </div>
                        </div>

                        @if ($i === $pakCount - 1)
                            <hr class="small" />
                            <div class="text-center">
                                <button type="submit" class="btn btn-orange">Создать {{ $pakCount == 1 ? 'упаковку' : 'упаковки' }}</button>
                            </div>
                        @endif
                    </div>
                @endfor
            </form>

            <div class="well block">
                <h3>Мульти-добавление упаковок</h3>
                <hr class="small" />
                <form role="form" action="" method="get">
                    <div class="form-group">
                        <span class="control-label">Введите необходимое количество упаковок (не более 10):</span>
                        <input type="text" class="form-control" name="count" value="{{ $pakCount }}" />
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Применить</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-packages-add-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection
