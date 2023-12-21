{{--
This file is part of MM2-dev project.
Description: Main page of the shop
--}}
@extends('layouts.master')

@section('title', 'Редактирование упаковки')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => "{$good->title} ({$package->city->title})", 'url' => url('/shop/management/goods/packages/' . $good->id)],
        ['title' => 'Упаковки', 'url' => url('/shop/management/goods/packages/city/' . $good->id . '/' . $package->city->id)],
        ['title' => 'Редактирование упаковки']
    ]])
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h4>Редактирование упаковки: {{ $good->title }} ({{ $package->city->title }})</h4>
                <hr class="small" />
                <form action="" role="form" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-xs-12">
                            @if(is_numeric($package->amount))
                                <span class="help-block-no-margin">
                                    <label for="amount">Количество:</label>
                                </span>
                            @endif
                        </div>
                        <div class="col-xs-12"></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group{{ $errors->has('amount') ? ' has-error' : '' }}">
                                <input id="amount" type="text" class="form-control" name="amount" placeholder="Количество" value="{{ old('amount') ?: $package->amount }}" required {{ autofocus_on_desktop() }}>
                                @if ($errors->has('amount'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('amount') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-12 -->
                        <div class="col-xs-12">
                            <div class="form-group has-feedback{{ $errors->has('measure') ? ' has-error' : '' }}">
                                <select name="measure" class="form-control" title="Единица измерения">
                                    <option value="">Единица измерения</option>
                                    <option value="{{ \App\GoodsPackage::MEASURE_GRAM }}" {{ (old('measure') ?: $package->measure) == \App\GoodsPackage::MEASURE_GRAM ? 'selected' : '' }}>г.</option>
                                    <option value="{{ \App\GoodsPackage::MEASURE_PIECE }}" {{ (old('measure') ?: $package->measure) == \App\GoodsPackage::MEASURE_PIECE ? 'selected' : '' }}>шт.</option>
                                    <option value="{{ \App\GoodsPackage::MEASURE_ML }}" {{ (old('measure') ?: $package->measure) == \App\GoodsPackage::MEASURE_ML ? 'selected' : '' }}>мл.</option>

                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>

                                @if ($errors->has('measure'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('measure') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-12 -->
                    </div> <!-- /.row -->
                    <div class="row">
                        <div class="col-xs-12">
                            @if(is_numeric($package->price))
                                <span class="help-block-no-margin">
                                    <label for="price">Стоимость:</label>
                                </span>
                            @endif
                        </div>
                        <div class="col-xs-12"></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group{{ $errors->has('price') ? ' has-error' : '' }}">
                                <input id="price" type="text" class="form-control" name="price" placeholder="Стоимость" value="{{ old('price') ?: $package->price }}" required>

                                @if ($errors->has('price'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('price') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-12 -->
                        <div class="col-xs-12">
                            <div class="form-group has-feedback{{ $errors->has('currency') ? ' has-error' : '' }}">
                                <select name="currency" class="form-control" title="Валюта">
                                    <option value="">Валюта</option>
                                    <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB }}" {{ (old('currency') ?: $package->currency) == \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB ? 'selected' : '' }}>Рубль (RUB)</option>
                                    <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC }}" {{ (old('currency') ?: $package->currency) == \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC ? 'selected' : '' }}>Bitcoin (BTC)</option>
                                    <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_USD }}" {{ (old('currency') ?: $package->currency) == \App\Packages\Utils\BitcoinUtils::CURRENCY_USD ? 'selected' : '' }}>Доллар (USD)</option>
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>

                                @if ($errors->has('currency'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('currency') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-12 -->
                    </div> <!-- /.row -->
                    {{--
                    <hr class="small" />
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="checkbox" style="margin-top: 1px">
                                <label>
                                    <input type="checkbox" name="qiwi_enabled" value="true" {{ (old('qiwi_enabled') ?: $package->qiwi_enabled) == true ? 'checked' : ''}}> Упаковку можно купить за QIWI
                                </label>
                            </div>
                        </div> <!-- /.col-xs-12 -->
                    </div> <!-- /.row -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group{{ $errors->has('qiwi_price') ? ' has-error' : '' }}">
                                <input id="qiwi_price" type="text" class="form-control" name="qiwi_price" placeholder="Стоимость при покупке за QIWI" value="{{ old('qiwi_price') ?: $package->qiwi_price }}">
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
                    <div class="row">
                        <div class="col-xs-12">
                            @if(is_numeric($package->net_cost))
                                <span class="help-block-no-margin">
                                    <label for="net_cost">Себестоимость (RUB):</label>
                                </span>
                            @endif
                        </div>
                        <div class="col-xs-12"></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group{{ $errors->has('net_cost') ? ' has-error' : '' }}">
                                <input id="net_cost" type="text" class="form-control" name="net_cost" placeholder="Себестоимость (RUB)" value="{{ old('net_cost') ?: $package->net_cost }}">

                                @if ($errors->has('net_cost'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('net_cost') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="checkbox" style="margin-top: 1px">
                                <label>
                                    <input type="checkbox" name="preorder" value="true" {{ (old('preorder') ?: $package->preorder) == true ? 'checked' : ''}}> Предзаказ
                                </label>
                            </div>
                        </div> <!-- /.col-xs-12 -->
                        <div class="col-xs-12">
                            <span class="help-block" style="margin-top: 0;">Работает только при режиме "Предзаказ".</span>
                        </div>
                    </div> <!-- /.row -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group has-feedback{{ $errors->has('preorder_time') ? ' has-error' : '' }}">
                                <select name="preorder_time" class="form-control" title="Время">
                                    <option value="">Время</option>
                                    <option value="{{ \App\GoodsPackage::PREORDER_TIME_24 }}" {{ (old('preorder_time') ?: $package->preorder_time) == \App\GoodsPackage::PREORDER_TIME_24 ? 'selected' : '' }}>24 часа</option>
                                    <option value="{{ \App\GoodsPackage::PREORDER_TIME_48 }}" {{ (old('preorder_time') ?: $package->preorder_time) == \App\GoodsPackage::PREORDER_TIME_48 ? 'selected' : '' }}>48 часов</option>
                                    <option value="{{ \App\GoodsPackage::PREORDER_TIME_72 }}" {{ (old('preorder_time') ?: $package->preorder_time) == \App\GoodsPackage::PREORDER_TIME_72 ? 'selected' : '' }}>72 часа</option>
                                    <option value="{{ \App\GoodsPackage::PREORDER_TIME_480 }}" {{ (old('preorder_time') ?: $package->preorder_time) == \App\GoodsPackage::PREORDER_TIME_480 ? 'selected' : '' }}>20 дней</option>
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                @if ($errors->has('preorder_time'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('preorder_time') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-xs-12">
                            <div class="dropdown form-group">
                                <input type="text" class="form-control" value="Платные услуги" readonly />
                                <ul class="dropdown-menu orange" role="menu">
                                    @if (count($services) > 0)
                                        <?php $packageServices = $package->services(); ?>
                                        @foreach ($services as $key => $service)
                                        <li role="presentation">
                                            <div class="checkbox dropdown-checkbox">
                                                <label>
                                                    <input type="checkbox" name="services[]" value="{{ $service->id }}" {{ (old('services.' . $key) ?: $packageServices->pluck('id')->contains($service->id)) ? 'checked' : '' }}> {{ $service->title }} - {{ $service->getHumanPrice() }}
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
                            @if(is_numeric($package->employee_reward))
                                <span class="help-block-no-margin">
                                    <label for="employee_reward">Выплата работнику:</label>
                                </span>
                            @endif
                        </div>
                        <div class="col-xs-12">
                            @if(is_numeric($package->employee_penalty))
                                <span class="help-block-no-margin">
                                    <label for="employee_penalty">
                                        Штраф работнику:
                                    </label>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group{{ $errors->has('employee_reward') ? ' has-error' : '' }}">
                                <input id="employee_reward" type="text" class="form-control" name="employee_reward" placeholder="Выплата работнику (руб.)" value="{{ old('employee_reward') ?: $package->employee_reward }}">

                                @if ($errors->has('employee_reward'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('employee_reward') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-xs-12">
                            <div class="form-group{{ $errors->has('employee_penalty') ? ' has-error' : '' }}">
                                <input id="employee_penalty" type="text" class="form-control" name="employee_penalty" placeholder="Штраф работнику (руб.)" value="{{ old('employee_penalty') ?: $package->employee_penalty }}">

                                @if ($errors->has('employee_penalty'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('employee_penalty') }}</strong>
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
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Отредактировать упаковку</button>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-packages-reminder')
        </div> <!-- /.col-sm-6 -->

    </div> <!-- /.row -->
@endsection