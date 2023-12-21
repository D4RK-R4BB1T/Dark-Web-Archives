@extends('layouts.master')

@section('title', 'Настройки обменника')

@section('content')
    @include('layouts.components.sections-menu')

    <div class="row">
        <div class="col-xs-24 col-sm-18 col-md-18 col-lg-19 pull-right animated fadeIn">
            <div class="well block">
                <h3>Настройки обменника</h3>
                <hr class="small" />
                <div class="row">
                    <div class="col-xs-12 col-sm-11 col-md-8 col-lg-6">
                        <span class="text-muted">Exchange ID:</span>
                    </div>
                    <div class="col-xs-12 col-sm-13 col-md-16">
                        {{ $exchange->id }}
                    </div>
                </div>
                <hr class="small" />
                <form action="" role="form" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="active" {{ (old('active') || $exchange->active) ? 'checked' : '' }}> Включить обменник для пользователей
                            </label>
                            <span class="help-block">
                                <strong>Включайте обменник только после того, как закончите тестирование обработчика и системы.</strong><br />
                                С помощью данной настройки можно также выключить обмен при возниковении неполадок на вашей стороне.
                            </span>
                        </div>
                    </div>

                    <hr class="small" />

                    <div class="form-group{{ $errors->has('description') ? ' has-error' : '' }}">
                        <textarea class="form-control" name="description" rows="3" placeholder="Описание обменника" required>{{ old('description') ?: $exchange->description }}</textarea>

                        @if ($errors->has('description'))
                            <span class="help-block">
                                <strong>{{ $errors->first('description') }}</strong>
                            </span>
                        @else
                            <span class="help-block">
                                Краткая информация, показываемая пользователю при осуществлении обмена. Тут могут быть дополнительные контакты и прочая информация.
                            </span>
                        @endif
                    </div>

                    <hr class="small" />

                    <div class="form-group{{ $errors->has('btc_rub_rate') ? ' has-error' : '' }}">
                        <input id="btc_rub_rate" type="text" class="form-control" name="btc_rub_rate" placeholder="Курс BTC к рублю" value="{{ old('btc_rub_rate') ?: $exchange->btc_rub_rate }}">
                        @if ($errors->has('btc_rub_rate'))
                            <span class="help-block">
                                <strong>{{ $errors->first('btc_rub_rate') }}</strong>
                            </span>
                        @else
                            <span class="help-block">
                                Введите курс обмена 1 BTC к рублю. Данное значение будет использоваться при расчётах со стороны пользователя в калькуляторе. <br />
                                <strong>Рекомендуется обновлять это значение автоматически при изменении курса используя метод update_rates.</strong>
                            </span>
                        @endif
                    </div>

                    <div class="form-group{{ $errors->has('reserve_time') ? ' has-error' : '' }}">
                        <input id="btc_rub_rate" type="text" class="form-control" name="reserve_time" placeholder="Время на оплату" value="{{ old('reserve_time') ?: $exchange->reserve_time }}">
                        @if ($errors->has('reserve_time'))
                            <span class="help-block">
                                <strong>{{ $errors->first('reserve_time') }}</strong>
                            </span>
                        @else
                            <span class="help-block">
                                Введите время (в минутах) на оплату заказа, спустя которое без нажатия на кнопку оплаты обмен автоматически отменится.
                            </span>
                        @endif
                    </div>

                    <div class="form-group{{ $errors->has('min_amount') ? ' has-error' : '' }}">
                        <input id="min_amount" type="text" class="form-control" name="min_amount" placeholder="Минимальная сумма обмена" value="{{ old('min_amount') ?: $exchange->min_amount }}">
                        @if ($errors->has('min_amount'))
                            <span class="help-block">
                                <strong>{{ $errors->first('min_amount') }}</strong>
                            </span>
                        @else
                            <span class="help-block">
                                Введите минимальную сумму обмена в рублях.
                            </span>
                        @endif
                    </div>

                    <div class="form-group{{ $errors->has('max_amount') ? ' has-error' : '' }}">
                        <input id="max_amount" type="text" class="form-control" name="max_amount" placeholder="Максимальная сумма обмена" value="{{ old('max_amount') ?: $exchange->max_amount }}">
                        @if ($errors->has('max_amount'))
                            <span class="help-block">
                                <strong>{{ $errors->first('max_amount') }}</strong>
                            </span>
                        @else
                            <span class="help-block">
                                Введите максимальную сумму обмена в рублях.
                            </span>
                        @endif
                    </div>

                    <div class="form-group{{ $errors->has('api_url') ? ' has-error' : '' }}">
                        <input id="api_url" type="text" class="form-control" name="api_url" placeholder="Адрес обработчика (%HANDLER%)" value="{{ old('api_url') ?: $exchange->api_url }}">
                        @if ($errors->has('api_url'))
                            <span class="help-block">
                                <strong>{{ $errors->first('api_url') }}</strong>
                            </span>
                        @else
                            <span class="help-block">
                                Введите полный адрес обработчика <strong>Solaris Exchange API</strong>, вместе с http://. Поддерживается работа с зоной .onion. <br />
                                <strong>Документация доступна по ссылке: <a href="{{ url("/help/employee/exchange_api") }}" target="_blank">{{ URL::to('/help/employee/exchange_api') }}</a></strong>
                            </span>
                        @endif
                    </div>

                    <div class="form-group{{ $errors->has('qiwi_api_key') ? ' has-error' : '' }}">
                        <input id="api_key" type="text" class="form-control" name="api_key" placeholder="Секретный ключ магазина (shop_key)" value="{{ old('api_key') ?: $exchange->api_key }}">

                        @if ($errors->has('api_key'))
                            <span class="help-block">
                                <strong>{{ $errors->first('api_key') }}</strong>
                            </span>
                        @endif
                    </div>

                    <hr />

                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Сохранить изменения</button>
                    </div>
                </form>
            </div>
            <div class="well block">
                <h3>Отладочная информация</h3>
                <hr class="small" />
                <div class="row">
                    <div class="col-xs-12 col-sm-14 col-md-11 col-lg-9">
                        <span class="text-muted">Последний запрос:</span>
                    </div>
                    <div class="col-xs-12 col-sm-10 col-md-13">
                        {{ $exchange->last_response_at ? $exchange->last_response_at->format('d.m.Y H:i') : '-' }}
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-10 col-md-11 col-lg-9">
                        <span class="text-muted">Последний ответ сервера:</span>
                    </div>
                    <div class="col-xs-24">
                        <pre style="min-height: 100px">{{ $exchange->last_response ?: '-' }}</pre>
                    </div>
                </div>
                <div class="text-center">
                    <a class="btn btn-orange" href="{{ url('/exchange/management/settings/init_test?_token='.csrf_token()) }}">Инициировать тестовый обмен</a>
                </div>
            </div>
        </div>
        <div class="col-xs-24 col-sm-6 col-md-6 col-lg-5 pull-left">
            @include('exchange.management.sidebar')
        </div> <!-- /.col-lg-5 -->
    </div> <!-- /.row -->
@endsection