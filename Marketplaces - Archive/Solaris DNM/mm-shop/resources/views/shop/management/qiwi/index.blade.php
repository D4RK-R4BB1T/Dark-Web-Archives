{{--
This file is part of MM2-dev project.
Description: Qiwi index page
--}}
@extends('layouts.master')

@section('title', 'Настройки QIWI-кошельков')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.qiwi.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-13 col-lg-14 animated fadeIn">
            @if ($shop->isExpired())
                <div class="alert alert-warning">Срок оплаты магазина истёк, QIWI-кошельки не будут проверяться.</div>
            @endif
            @if ($shop->isQiwiApiEnabled())
                <div class="alert alert-warning">
                    Магазин находится в режиме интеграции с API.<br />
                    Информация в таблице ниже получена с сервера и не может быть отредактирована.
                </div>
            @endif
            <div class="well block">
                <h3 class="one-line">Настройки QIWI-кошельков</h3>
                <hr class="small" />
                @if(count($qiwiWallets) > 0)
                    <table class="table table-header" style="margin-bottom: 0">
                        <thead>
                        <tr>
                            <td>Аккаунт</td>
                            <td>Баланс</td>
                            <td><span class="hint--top" aria-label="Поступления за сегодня / Дневной лимит">Лимит (д)</span></td>
                            <td><span class="hint--top" aria-label="Поступления за текущий месяц / Месячный лимит">Лимит (м)</span></td>
                            <td>Статус</td>
                            <td>Последняя проверка</td>
                            @if (!$shop->isQiwiApiEnabled())<td class="col-xs-3"></td>@endif
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($qiwiWallets as $qiwiWallet)
                            <tr>
                                <td>{{ $qiwiWallet->login }}</td>
                                <td>{{ $qiwiWallet->last_checked_at ? human_price($qiwiWallet->balance, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) : '-' }}</td>
                                <td>{{ round_price($qiwiWallet->current_day_income, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }} / {{ round_price($qiwiWallet->daily_limit, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}</td>
                                <td>{{ round_price($qiwiWallet->current_month_income, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }} / {{ round_price($qiwiWallet->monthly_limit, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}</td>
                                <td>{!! $qiwiWallet->last_checked_at ? $qiwiWallet->getHumanStatus() : '-' !!}</td>
                                <td>{{ $qiwiWallet->last_checked_at ? $qiwiWallet->last_checked_at->format('d.m.Y H:i') : '-' }}</td>
                                @if (!$shop->isQiwiApiEnabled())
                                    <td class="text-right" style="font-size: 15px">
                                        <a class="dark-link hint--top" aria-label="Редактировать" href="{{ url('/shop/management/qiwi/edit/'.$qiwiWallet->id) }}"><i class="glyphicon glyphicon-edit"></i></a>
                                        <a class="text-danger hint--top hint--error" aria-label="Удалить" href="{{ url('/shop/management/qiwi/delete/'.$qiwiWallet->id) }}"><i class="glyphicon glyphicon-remove"></i></a>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-info" style="margin-bottom: 0">Ни одного QIWI-кошелька не добавлено.</div>
                @endif
                @if (!$shop->isQiwiApiEnabled())
                    <hr class="small" />
                    <div class="text-center">
                        <a class="btn btn-orange" href="{{ url("/shop/management/qiwi/add") }}">Добавить QIWI-кошелек</a>
                    </div>
                @endif
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-5 animated fadeIn">
            @include('shop.management.components.block-qiwi-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection