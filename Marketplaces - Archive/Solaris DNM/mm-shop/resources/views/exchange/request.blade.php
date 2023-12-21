@extends('layouts.master')

@section('title', 'Просмотр обмена')

@section('content')
    @include('layouts.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
        'breadcrumbs' =>
        [
            BREADCRUMB_EXCHANGE,
            ['title' => 'Просмотр обмена']
        ]
    ])

    <div class="row">
        <div class="col-xs-24 col-sm-18 col-md-18 col-lg-19 pull-right animated fadeIn">
            <div class="well block">
                <h3>Информация об обмене</h3>
                <hr class="small" />
                @if ($exchangeRequest->status === \App\QiwiExchangeRequest::STATUS_CREATING)
                    @section('header_scripts')
                        <meta http-equiv="refresh" content="5; URL=/exchange/{{ $exchangeRequest->id }}">
                    @endsection

                    <img src="{{ url('/assets/img/select2-spinner.gif') }}" /> &nbsp;
                    Пожалуйста, подождите, мы инициируем обмен на сервере обменника. Это может занять несколько секунд.

                @elseif ($exchangeRequest->status === \App\QiwiExchangeRequest::STATUS_PAID_REQUEST)
                    @section('header_scripts')
                        <meta http-equiv="refresh" content="5; URL=/exchange/{{ $exchangeRequest->id }}">
                    @endsection

                    <img src="{{ url('/assets/img/select2-spinner.gif') }}" /> &nbsp;
                    Пожалуйста, подождите, мы сообщаем серверу обменника о вашей оплате. Это может занять несколько секунд.

                @elseif ($exchangeRequest->status === \App\QiwiExchangeRequest::STATUS_PAID)
                    @section('header_scripts')
                        <meta http-equiv="refresh" content="30; URL={{ url('/exchange/'.$exchangeRequest->id) }}">
                    @endsection

                    <img src="{{ url('/assets/img/select2-spinner.gif') }}" /> &nbsp;
                    Обменник проверяет ваш платёж.
                @elseif ($exchangeRequest->status === \App\QiwiExchangeRequest::STATUS_FINISHED)
                    <div class="alert alert-success">
                        Обмен успешно завершен, биткоины отправлены на ваш счёт.
                    </div>
                @elseif ($exchangeRequest->status === \App\QiwiExchangeRequest::STATUS_CANCELLED)
                    <div class="alert alert-warning">
                        Обмен был отменён.
                        @if ($exchangeRequest->error_reason)
                            <br /> Причина: <strong>{{ $exchangeRequest->error_reason }}</strong>
                        @endif
                    </div>
                @elseif ($exchangeRequest->status === \App\QiwiExchangeRequest::STATUS_PAID_PROBLEM)
                    <div class="alert alert-warning">
                        Обменник отметил заказ как неоплаченный или при его отправке возникла техническая проблема. <br />
                        @if ($exchangeRequest->error_reason)
                            Описание ошибки: <strong>{{ $exchangeRequest->error_reason }}</strong><br />
                        @endif
                        <ul>
                            <li>
                                Свяжитесь с обменником через <a href="{{ url('/shop/' . \App\Shop::getDefaultShop()->slug . '/message') }}">личные сообщения в магазине</a> или используя другие контактные данные.
                            </li>
                            <li>
                                Если ситуацию решить не получается, обратитесь на форум DarkCon к пользователю <strong>Exchange_Support</strong>: (<a href="http://darkconbp235ybwn.onion/index.php?action=pmm;sa=send;u=6891" target="_blank">http://darkconbp235ybwn.onion/index.php?action=pmm;sa=send;u=6891</a>).
                                В сообщении отправьте всю указанную ниже информацию и предоставьте скриншоты или фотографии для доказательства оплаты.
                            </li>
                        </ul>
                    </div>
                    <form action="" method="post" class="form-horizontal">
                        {{ csrf_field() }}
                        <div class="form-group">
                            <label class="col-xs-6 col-lg-4 control-label">Логин:</label>
                            <div class="col-xs-18">
                                <p class="form-control-static">{{ Auth::user()->getPublicName() }}</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 col-lg-4 control-label">Адрес магазина:</label>
                            <div class="col-xs-18">
                                <p class="form-control-static">{{ URL::to('/') }}</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 col-lg-4 control-label">Номер обмена:</label>
                            <div class="col-xs-18">
                                <p class="form-control-static">{{ $exchangeRequest->id }}</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 col-lg-4 control-label">Курс обмена:</label>
                            <div class="col-xs-18">
                                <p class="form-control-static">1 BTC = {{ human_price($exchangeRequest->btc_rub_rate, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 col-lg-4 control-label">Сумма обмена:</label>
                            <div class="col-xs-18">
                                <p class="form-control-static">
                                    {{ human_price($exchangeRequest->btc_amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}
                                </p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 col-lg-4 control-label">Сумма к оплате:</label>
                            <div class="col-xs-18">
                                <p class="form-control-static">
                                    {{ traverse($exchangeRequest, 'qiwiExchangeTransaction->pay_amount') ?: '-' }}
                                </p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-6 col-lg-4 control-label">Срок оплаты:</label>
                            <div class="col-xs-18">
                                <p class="form-control-static">
                                    {{ $exchangeRequest->created_at->addMinutes(15)->format('d.m.Y H:i') }}
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-6 col-lg-4 control-label">Кошелек:</label>
                            <div class="col-xs-18">
                                <p class="form-control-static">
                                    {{ traverse($exchangeRequest, 'qiwiExchangeTransaction->pay_address') ?: '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-6 col-lg-4 control-label">Комментарий:</label>
                            <div class="col-xs-18">
                                <p class="form-control-static">
                                    {{ traverse($exchangeRequest, 'qiwiExchangeTransaction->pay_comment') ?: '-' }}
                                </p>
                            </div>
                        </div>

                        @if (traverse($exchangeRequest, 'qiwiExchangeTransaction->pay_need_input'))
                            <div class="form-group">
                                <label class="col-xs-6 col-lg-4 control-label">{{ traverse($exchangeRequest, 'qiwiExchangeTransaction->pay_input_description') }}:</label>
                                <div class="col-xs-18">
                                    <p class="form-control-static">
                                        {{ traverse($exchangeRequest, 'input') ?: '-' }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </form>
                @elseif($exchangeRequest->status === \App\QiwiExchangeRequest::STATUS_RESERVED)
                    @if ($exchangeRequest->created_at->addMinutes($exchangeRequest->qiwiExchange->reserve_time)->lte(\Carbon\Carbon::now()))
                        <div class="alert alert-info">
                            Срок оплаты просрочен, дождитесь удаления заявки и создайте новую.
                        </div>
                    @else
                        <form action="" method="post" class="form-horizontal">
                            {{ csrf_field() }}
                            <div class="form-group">
                                <label class="col-xs-6 col-lg-4 control-label">Курс обмена:</label>
                                <div class="col-xs-18">
                                    <p class="form-control-static">1 BTC = {{ human_price($exchangeRequest->btc_rub_rate, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-6 col-lg-4 control-label">Сумма обмена:</label>
                                <div class="col-xs-18">
                                    <p class="form-control-static">
                                        {{ human_price($exchangeRequest->btc_amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}
                                    </p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-6 col-lg-4 control-label">Сумма к оплате:</label>
                                <div class="col-xs-18">
                                    <p class="form-control-static">
                                        {{ traverse($exchangeRequest, 'qiwiExchangeTransaction->pay_amount') ?: '-' }}
                                    </p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-6 col-lg-4 control-label">Срок оплаты:</label>
                                <div class="col-xs-18">
                                    <p class="form-control-static">
                                        {{ $exchangeRequest->created_at->addMinutes($exchangeRequest->qiwiExchange->reserve_time)->format('d.m.Y H:i') }}
                                    </p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-xs-6 col-lg-4 control-label">Кошелек:</label>
                                <div class="col-xs-18">
                                    <input type="text" class="form-control" value="{{ traverse($exchangeRequest, 'qiwiExchangeTransaction->pay_address') ?: '-' }}" readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-xs-6 col-lg-4 control-label">Комментарий:</label>
                                <div class="col-xs-18">
                                    <input type="text" class="form-control" value="{{ traverse($exchangeRequest, 'qiwiExchangeTransaction->pay_comment') ?: '-' }}" readonly>
                                    <span class="help-block">
                                        Важно переводить деньги именно с таким примечанием, иначе платеж не будет обработан автоматически.
                                    </span>
                                </div>
                            </div>

                            @if (traverse($exchangeRequest, 'qiwiExchangeTransaction->pay_need_input'))
                                <div class="form-group {{ $errors->has('input') ? ' has-error' : '' }}">
                                    <label class="col-xs-6 col-lg-4 control-label">{{ traverse($exchangeRequest, 'qiwiExchangeTransaction->pay_input_description') }}:</label>
                                    <div class="col-xs-18">
                                        <input type="text" name="input" class="form-control" value="{{ old('input') }}">
                                        @if ($errors->has('input'))
                                            <span class="help-block">
                                                <strong>{{ $errors->first('input') }}</strong>
                                            </span>
                                        @else
                                            <span class="help-block">Не вводите свой пароль в данное поле, эта информация передается обменнику.</span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <hr class="small" />
                            <div class="text-center">
                                <div class="alert alert-warning">
                                    После совершения оплаты нажмите на кнопку "Заявка оплачена" ниже. <br />
                                    <strong>Не нажимайте на кнопку без совершения оплаты - это может привести к блокировке аккаунта.</strong>
                                </div>
                            </div>
                            <hr class="small" />
                            <div class="text-center">
                                <button type="submit" name="action" value="cancel" class="btn btn-warning">Отменить обмен</button>
                                <button type="submit" name="action" value="paid" class="btn btn-success">Заявка оплачена</button>
                            </div>
                        </form>
                    @endif
                @endif
            </div>
        </div>
        <div class="col-xs-24 col-sm-6 col-md-6 col-lg-5 pull-left">
            @include('exchange.sidebar')
        </div> <!-- /.col-lg-5 -->
    </div> <!-- /.row -->
@endsection