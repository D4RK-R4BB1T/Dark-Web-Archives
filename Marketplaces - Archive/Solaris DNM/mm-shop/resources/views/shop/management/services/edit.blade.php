{{--
This file is part of MM2-dev project.
Description: Paid service edit
--}}
@extends('layouts.master')

@section('title', 'Редактирование платной услуги')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => 'Платные услуги', 'url' => url('/shop/management/goods/services')],
        ['title' => 'Редактирование платной услуги']
    ]])

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Редактирование платной услуги</h3>
                <hr class="small" />
                <form action="" role="form" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
                                <input id="title" type="text" class="form-control" name="title" placeholder="Опишите услугу" value="{{ old('title') ?: $service->title }}" required {{ autofocus_on_desktop() }}>
                                @if ($errors->has('title'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('title') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-12 -->
                        <div class="col-xs-6">
                            <div class="form-group{{ $errors->has('price') ? ' has-error' : '' }}">
                                <input id="price" type="text" class="form-control" name="price" placeholder="Стоимость" value="{{ old('price') ?: $service->price }}" required>

                                @if ($errors->has('price'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('price') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-6 -->
                        <div class="col-xs-6">
                            <div class="form-group has-feedback{{ $errors->has('currency') ? ' has-error' : '' }}">
                                <select name="currency" class="form-control" title="Валюта">
                                    <option value="">Валюта</option>
                                    <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB }}" {{ (old('currency') ?: $service->currency) == \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB ? 'selected' : '' }}>Рубль (RUB)</option>
                                    <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC }}" {{ (old('currency') ?: $service->currency) == \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC ? 'selected' : '' }}>Bitcoin (BTC)</option>
                                    <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_USD }}" {{ (old('currency') ?: $service->currency) == \App\Packages\Utils\BitcoinUtils::CURRENCY_USD ? 'selected' : '' }}>Доллар (USD)</option>
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>

                                @if ($errors->has('currency'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('currency') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-6 -->
                    </div> <!-- /.row -->
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Отредактировать услугу</button>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-services-reminder')
        </div> <!-- /.col-sm-6 -->

    </div> <!-- /.row -->
@endsection