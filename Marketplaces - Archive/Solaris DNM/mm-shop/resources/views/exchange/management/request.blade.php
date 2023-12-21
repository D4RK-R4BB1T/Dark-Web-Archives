@extends('layouts.master')

@section('title', 'Просмотр обмена')

@section('content')
    @include('layouts.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
        'breadcrumbs' =>
        [
            BREADCRUMB_EXCHANGE_MANAGEMENT,
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
                        <meta http-equiv="refresh" content="5; URL=/exchange/management/{{ $exchangeRequest->id }}">
                    @endsection

                    <img src="{{ url('/assets/img/select2-spinner.gif') }}" /> &nbsp;
                    Пожалуйста, подождите, мы инициируем обмен на сервере обменника. Это может занять несколько секунд.
                    <hr class="small" />
            @elseif ($exchangeRequest->status === \App\QiwiExchangeRequest::STATUS_PAID_REQUEST)
                    @section('header_scripts')
                        <meta http-equiv="refresh" content="5; URL=/exchange/management/{{ $exchangeRequest->id }}">
                    @endsection

                    <img src="{{ url('/assets/img/select2-spinner.gif') }}" /> &nbsp;
                    Пожалуйста, подождите, мы сообщаем серверу обменника об оплате. Это может занять несколько секунд.
                    <hr class="small" />
            @elseif ($exchangeRequest->status === \App\QiwiExchangeRequest::STATUS_PAID)
                    @section('header_scripts')
                        <meta http-equiv="refresh" content="30; URL=/exchange/management/{{ $exchangeRequest->id }}">
                    @endsection

                    <img src="{{ url('/assets/img/select2-spinner.gif') }}" /> &nbsp;
                    Обменник проверяет платёж.
                    <hr class="small" />

            @elseif ($exchangeRequest->status === \App\QiwiExchangeRequest::STATUS_FINISHED)
                    <div class="alert alert-success">
                        Обмен успешно завершен, биткоины отправлены на счёт.
                    </div>
                @elseif ($exchangeRequest->status === \App\QiwiExchangeRequest::STATUS_CANCELLED)
                    <div class="alert alert-warning">
                        Обмен был отменён.
                        @if ($exchangeRequest->error_reason)
                            <br />Причина: <strong>{{ $exchangeRequest->error_reason }}</strong>
                        @endif
                    </div>
                    <hr class="small" />
                @elseif ($exchangeRequest->status === \App\QiwiExchangeRequest::STATUS_PAID_PROBLEM)
                    <div class="alert alert-warning">
                        Обменник отметил заказ как неоплаченный или при отправке запроса возникла ошибка. <br />
                        @if ($exchangeRequest->error_reason)
                            Причина: <strong>{{ $exchangeRequest->error_reason }}</strong>
                        @endif
                    </div>
                    <hr class="small" />
                    <a class="btn btn-success" href="{{ url('/exchange/management/finish/'.$exchangeRequest->id.'?_token='.csrf_token()) }}">Отдать BTC пользователю</a>
                    @if ($exchangeRequest->qiwiExchange->trusted)
                        <a class="btn btn-danger" href="{{ url('/exchange/management/cancel/'.$exchangeRequest->id.'?_token='.csrf_token()) }}">Отменить обмен в свою пользу</a>
                    @endif

                    <hr class="small" />
                @elseif($exchangeRequest->status === \App\QiwiExchangeRequest::STATUS_RESERVED)
                    <div class="alert alert-info">
                        Пользователь оплачивает заказ.
                    </div>
                @endif

                <form action="" method="post" class="form-horizontal">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label class="col-xs-6 col-lg-4 control-label">Логин:</label>
                        <div class="col-xs-18">
                            <p class="form-control-static">{{ $exchangeRequest->user->getPublicName() }}</p>
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
            </div>
        </div>
        <div class="col-xs-24 col-sm-6 col-md-6 col-lg-5 pull-left">
            @include('exchange.management.sidebar')
        </div> <!-- /.col-lg-5 -->
    </div> <!-- /.row -->
@endsection