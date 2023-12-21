{{--
This file is part of MM2-dev project.
Description: Qiwi operations page
--}}
@extends('layouts.master')

@section('title', 'Операции по QIWI-кошелькам')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.qiwi.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            @include('shop.management.qiwi.components.components-operations-filter')
            <div class="well block">
                <h3>
                    Информация об операциях ({{ $periodStart->format('d.m.Y') }} - {{ $periodEnd->format('d.m.Y') }})
                </h3>
                <hr class="small" />
                <p>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Общее количество платежей:</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                            {{ $qiwiStats['count'] }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Общая сумма платежей:</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                            {{ human_price($qiwiStats['total'], \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}
                        </div>
                    </div>
                </p>
            </div>
            <div class="well block good-info">
                <h3>Операции по QIWI-кошелькам</h3>
                @if ($transactions->count() == 0)
                    <hr class="small" />
                    <div class="alert alert-info" style="margin-bottom: 0">Не найдено ни одной операции.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>Кошелек</td>
                                <td>Заказ</td>
                                <td>Сумма</td>
                                <td>Отправитель</td>
                                <td>Статус</td>
                                <td>Дата создания</td>
                                <td>Дата оплаты</td>
                                <td>Дата проверки</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($transactions as $transaction)
                                <tr class="{{ $transaction->status == \App\QiwiTransaction::STATUS_PAID ? 'bg-success' : '' }}">
                                    <td>
                                        @if ($transaction->qiwiWallet)
                                            {{ $transaction->qiwiWallet->login }}
                                        @else
                                            <span class="hint--top dashed" aria-label="Кошелек не найден">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($transaction->order)
                                            @if (Auth::user()->can('management-sections-orders'))
                                                <a href="{{ url('/shop/management/orders/'.$transaction->order->id) }}">
                                            @endif
                                            {{ $transaction->order->_stub_good()->title }} ({{ $transaction->order->_stub_package()->getHumanWeight() }})
                                            @if (Auth::user()->can('management-sections-orders'))
                                                </a>
                                            @endif
                                        @else
                                            <span class="hint--top dashed" aria-label="Заказ не найден">-</span>
                                        @endif
                                    </td>
                                    <td>{{ human_price($transaction->amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}</td>
                                    <td>{{ $transaction->sender ?: '-' }}</td>
                                    <td>{{ $transaction->getHumanStatus() }}</td>
                                    <td>{{ $transaction->created_at->format('d.m.Y в H:i') }}</td>
                                    <td>{{ $transaction->paid_at ? $transaction->paid_at->format('d.m.Y в H:i') : '-' }}</td>
                                    <td>{{ $transaction->last_checked_at ? $transaction->last_checked_at->format('d.m.Y в H:i') : '-' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($transactions->total() > $transactions->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $transactions->appends(request()->input())->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div> <!-- /.col-sm-18 -->
    </div> <!-- /.row -->
@endsection