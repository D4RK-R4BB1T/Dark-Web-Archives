@extends('layouts.master')

@section('title', 'Создание промо-кода :: Скидки')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_DISCOUNTS,
        ['title' => 'Промо-коды', 'url' => url('/shop/management/discounts/promo')],
        ['title' => 'Создание промо-кода']
    ]])

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.discounts.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Создание промо-кода</h3>
                <hr class="small" />
                <form action="" role="form" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="text-center">
                        <h4>Выберите тип скидки</h4>
                    </div>
                    <div class="row">
                        <div class="col-xs-24 col-sm-11">
                            <div class="radio">
                                <label>
                                    <input type="radio" name="discount_mode" value="price" {{ (!old('discount_mode') || old('discount_mode') === 'price') ? 'checked' : '' }}>
                                    <strong>Промо-код на определенную сумму</strong>
                                </label>
                                <span class="help-block">
                                    Если сумма скидки больше суммы покупки - остаток сгорает.
                                </span>
                            </div>
                            <div class="row">
                                <div class="col-xs-12">
                                    <div class="form-group{{ $errors->has('price_amount') ? ' has-error' : '' }}">
                                        <input id="price_amount" type="text" class="form-control" name="price_amount" placeholder="Сумма скидки" value="{{ old('price_amount') }}">
                                        @if ($errors->has('price_amount'))
                                            <span class="help-block">
                                                <strong>{{ $errors->first('price_amount') }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div> <!-- /.col-xs-12 -->
                                <div class="col-xs-12">
                                    <div class="form-group has-feedback{{ $errors->has('price_currency') ? ' has-error' : '' }}">
                                        <select name="price_currency" class="form-control" title="Валюта">
                                            <option value="">Валюта</option>
                                            <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB }}" {{ old('price_currency') == \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB ? 'selected' : '' }}>Рубль (RUB)</option>
                                            <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC }}" {{ old('price_currency') == \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC ? 'selected' : '' }}>Bitcoin (BTC)</option>
                                            <option value="{{ \App\Packages\Utils\BitcoinUtils::CURRENCY_USD }}" {{ old('price_currency') == \App\Packages\Utils\BitcoinUtils::CURRENCY_USD ? 'selected' : '' }}>Доллар (USD)</option>
                                        </select>
                                        <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>

                                        @if ($errors->has('price_currency'))
                                            <span class="help-block">
                                                <strong>{{ $errors->first('price_currency') }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div> <!-- /.col-xs-12 -->
                            </div> <!-- /.row -->
                        </div>

                        <div class="col-xs-24 col-sm-11 col-sm-offset-2">
                            <div class="radio">
                                <label>
                                    <input type="radio" name="discount_mode" value="percent" {{ (old('discount_mode') === 'percent') ? 'checked' : '' }}>
                                    <strong>Промо-код на процент от стоимости</strong>
                                </label>
                            </div>
                            <div class="form-group{{ $errors->has('percent_amount') ? ' has-error' : '' }}">
                                <input id="percent_amount" type="text" class="form-control" name="percent_amount" placeholder="Величина скидки (%), только цифры" value="{{ old('percent_amount') }}">

                                @if ($errors->has('percent_amount'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('percent_amount') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-12 -->
                    </div>

                    <hr class="small" />

                    <div class="text-center">
                        <h4>Выберите количество использований</h4>
                    </div>

                    <div class="row">
                        <div class="col-xs-24 col-sm-11">
                            <div class="radio">
                                <label>
                                    <input type="radio" name="mode" value="single_use" {{ (!old('mode') || old('mode') === 'single_use') ? 'checked' : '' }}>
                                    <strong>Одноразовое использование</strong>
                                </label>
                                <span class="help-block">
                                    Промокод доступен к использованию один раз, после использования становится неактивным.
                                </span>
                            </div>
                        </div>

                        <div class="col-xs-24 col-sm-11 col-sm-offset-2">
                            <div class="radio">
                                <label>
                                    <input type="radio" name="mode" value="until_date" {{ (old('mode') === 'until_date') ? 'checked' : '' }}>
                                    <strong>Неограниченное использование</strong>
                                </label>
                                <span class="help-block">
                                    Промокод доступен к использованию неограниченное количество раз.
                                </span>
                            </div>
                        </div> <!-- /.col-xs-12 -->
                    </div>

                    <hr class="small" />

                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group {{ $errors->has('expires_at') ? ' has-error' : '' }}">
                                <input id="title" type="date" class="form-control" name="expires_at" placeholder="Срок действия (ГГГГ-ММ-ДД)" value="{{ old('expires_at') }}">
                                @if ($errors->has('expires_at'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('expires_at') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Укажите дату в формате ГГГГ-ММ-ДД (например, {{ \Carbon\Carbon::now()->format('Y-m-d') }}) или оставьте поле пустым. <br />
                                        Промокоды любого вида прекратят свою работу после указанной даты.
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-24 -->
                    </div> <!-- /.row -->

                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Создать промо-код</button>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-discounts-reminder')
        </div> <!-- /.col-sm-6 -->

    </div> <!-- /.row -->
@endsection