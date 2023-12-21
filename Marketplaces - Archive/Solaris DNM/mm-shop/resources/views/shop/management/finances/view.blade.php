{{--
This file is part of MM2-dev project.
Description: Finances view wallet page
--}}
@extends('layouts.master')

@section('title', 'История кошелька :: Финансы')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.finances.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">История кошелька: {{ $wallet->title }}</h3>
                <hr class="small" />
                @if ($operations->count() > 0)
                    <div class="table-responsive">
                    <table class="table table-header" style="margin-bottom: 0;">
                        <thead>
                        <tr>
                            <td class="col-xs-4 col-lg-4">Сумма</td>
                            <td class="col-xs-5 col-lg-5">Время</td>
                            <td class="col-xs-8 col-lg-8">Адрес</td>
                            <td class="col-xs-7 col-lg-5">Описание</td>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($operations as $operation)
                            <tr>
                                <td style="">
                                    @if($operation->amount > 0)
                                        <span class="text-success" style="position: relative; top: 1px"><i class="glyphicon glyphicon-plus-sign"></i></span>&nbsp;{{ human_price($operation->amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}
                                    @else
                                        <span class="text-danger" style="position: relative; top: 1px;"><i class="glyphicon glyphicon-minus-sign"></i></span>&nbsp;{{ human_price(-$operation->amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}
                                    @endif
                                </td>
                                <td>{{ $operation->created_at->format('d.m.Y в H:i') }}</td>
                                <td style="word-wrap: break-word">{{ $operation->wallet->segwit_wallet }}</td>
                                <td style="text-overflow: ellipsis; word-wrap: break-word;">{{ $operation->description }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                    @if ($operations->total() > $operations->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $operations->links() }}
                        </div>
                    @endif
                @else
                    <div class="alert alert-info" style="margin-bottom: 0">Платежей не найдено</div>
                @endif
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-18 -->

        {{--<div class="col-sm-5 animated fadeIn">--}}
        {{--@include('shop.management.components.block-finances-reminder')--}}
        {{--</div> <!-- /.col-sm-6 -->--}}

    </div> <!-- /.row -->
@endsection