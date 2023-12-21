@extends('layouts.master')

@section('title', 'История обменов')

@section('content')
    @include('layouts.components.sections-menu')

    <div class="row">
        <div class="col-xs-24 col-sm-18 col-md-18 col-lg-19 pull-right animated fadeIn">
            <div class="well block">
                <h3>История обменов</h3>
                <hr class="small" />
                @if (count($exchangeRequests) > 0)
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td>Обменник</td>
                                <td>Сумма в BTC</td>
                                <td>Курс обмена</td>
                                <td>Статус</td>
                                <td>Дата создания</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($exchangeRequests as $exchangeRequest)
                                <tr @if ($exchangeRequest->test_mode)style="font-style: italic" class="bg-warning"@endif
                                    @if (!$exchangeRequest->test_mode && $exchangeRequest->status == \App\QiwiExchangeRequest::STATUS_PAID_PROBLEM)class="bg-danger"@endif>
                                    <td><a href="{{ url('/exchange/'.$exchangeRequest->id) }}">{{ $exchangeRequest->id }}</a></td>
                                    <td><a href="{{ url('/exchange/'.$exchangeRequest->id) }}">{{ $exchangeRequest->qiwiExchange->title }}</a></td>
                                    <td><span class="hint--top dashed" aria-label="На момент обмена: {{ human_price($exchangeRequest->btc_amount * $exchangeRequest->btc_rub_rate, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}">{{ human_price($exchangeRequest->btc_amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}</span></td>
                                    <td>{{ human_price($exchangeRequest->btc_rub_rate, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}</td>
                                    <td>
                                        {{ $exchangeRequest->getHumanStatus() }}
                                        @if ($exchangeRequest->status === \App\QiwiExchangeRequest::STATUS_RESERVED && $exchangeRequest->test_mode)
                                            &nbsp; <a class="btn btn-sm btn-default" href="{{ url('/exchange/management/settings/test_paid/'.$exchangeRequest->id.'?_token='.csrf_token()) }}">оплатить</a>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $exchangeRequest->created_at->format('d.m.Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @if ($exchangeRequests->total() > $exchangeRequests->perPage())
                            <hr class="small" />
                            <div class="text-center">
                                {{ $exchangeRequests->appends(request()->input())->links() }}
                            </div>
                        @endif
                    </div>
                @else
                    <div class="alert alert-info">Не найдено ни одного обмена.</div>
                @endif
            </div>
        </div>
        <div class="col-xs-24 col-sm-6 col-md-6 col-lg-5 pull-left">
            @include('exchange.sidebar')
        </div> <!-- /.col-lg-5 -->
    </div> <!-- /.row -->
@endsection