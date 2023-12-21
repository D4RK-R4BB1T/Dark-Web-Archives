{{--
This file is part of MM2-dev project.
Description: Accounting lot add
--}}
@extends('layouts.master')

@section('title', 'Добавление партии :: Учет товаров :: Статистикв')

@section('content')
    @include('shop.management.components.sections-menu')

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.stats.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Добавление партии</h3>
                <hr class="small" />
                <form action="" role="form" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group has-feedback {{ $errors->has('good') ? 'has-error' : '' }}">
                                <select name="good" class="form-control" title="Выберите товар">
                                    <option value="">Выберите товар</option>
                                    @foreach ($goods as $good)
                                        <option value="{{ $good->id }}" {{ old('good') == $good->id ? 'selected' : '' }}>{{ $good->title }}</option>
                                    @endforeach
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                @if ($errors->has('good'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('good') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-24 -->
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group{{ $errors->has('amount') ? ' has-error' : '' }}">
                                <input id="amount" type="text" class="form-control" name="amount" placeholder="Количество" value="{{ old('amount') }}" required>
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
                                    <option value="{{ \App\GoodsPackage::MEASURE_GRAM }}" {{ old('measure') == \App\GoodsPackage::MEASURE_GRAM ? 'selected' : '' }}>г.</option>
                                    <option value="{{ \App\GoodsPackage::MEASURE_PIECE }}" {{ old('measure') == \App\GoodsPackage::MEASURE_PIECE ? 'selected' : '' }}>шт.</option>
                                    <option value="{{ \App\GoodsPackage::MEASURE_ML }}" {{ old('measure') == \App\GoodsPackage::MEASURE_ML ? 'selected' : '' }}>мл.</option>
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>

                                @if ($errors->has('measure'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('measure') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Для правильного учета товара единица измерения должна быть такая же, как и у упаковок выбранного товара.
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-12 -->
                    </div> <!-- /.row -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group{{ $errors->has('price') ? ' has-error' : '' }}">
                                <input id="price" type="text" class="form-control" name="price" placeholder="Стоимость партии" value="{{ old('price') }}" required>

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
                                    <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB }}" {{ old('currency') == \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB ? 'selected' : '' }}>Рубль (RUB)</option>
                                    <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC }}" {{ old('currency') == \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC ? 'selected' : '' }}>Bitcoin (BTC)</option>
                                    <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_USD }}" {{ old('currency') == \App\Packages\Utils\BitcoinUtils::CURRENCY_USD ? 'selected' : '' }}>Доллар (USD)</option>
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
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group {{ $errors->has("note") ? ' has-error' : '' }}">
                                <textarea class="form-control" name="note" rows="3" title="Заметка" placeholder="Заметка">{{ old("note") }}</textarea>
                                @if ($errors->has("note"))
                                    <span class="help-block">
                                        <strong>{{ $errors->first("note") }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Добавить партию</button>
                        &nbsp;
                        <a class="text-muted" href="{{ URL::previous() }}">вернуться назад</a>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-stats-accounting-lot-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection