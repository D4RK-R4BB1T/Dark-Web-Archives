{{-- 
This file is part of MM2 project. 
Description: Displays when shop is created and awaiting for the payment.
--}}
@extends('layouts.master')

@section('title', 'Создание аккаунта')

@section('header_scripts')
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-10 col-md-offset-7 auth-container">
            <div class="panel panel-modal">
                <div class="panel-heading">Оплата статуса продавца</div>
                <div class="panel-body">
                    @if (session('not_enough_money'))
                        <div class="alert orange animated fadeIn">
                            <i class="fa fa-info-circle"></i> Сумма платежей недостаточна для создания магазина. Текущий баланс: {{ round(session('not_enough_money'), 2) }}$.
                        </div>
                    @endif

                    @if (!\App\Packages\Utils\BitcoinUtils::isPaymentsEnabled())
                        <div class="alert orange animated fadeIn">
                            <i class="fa fa-info-circle"></i> Мы испытываем проблемы с приемом платежей, обработка может занимать несколько больше времени, чем обычно.
                        </div>
                    @endif
                    <p>
                        Для регистрации аккаунта продавца и создания своего моментального магазина на площадке необходимо
                        перевести <strong>{{ config('mm2.shop_usd_price') }} USD ({{ usd2btc(config('mm2.shop_usd_price')) }} BTC)</strong> в течение 15 минут на
                        биткоин-кошелек, который указан ниже.
                    </p>

                    <form class="form-horizontal" role="form">
                        <div class="form-group">
                            <div class="col-md-24">
                                <input id="wallet" type="text" class="form-control" name="wallet" value="{{ Auth::user()->primaryWallet()->segwit_wallet }}">
                            </div>
                        </div> <!-- /.form-group -->

                        <div class="form-group">
                            <div class="col-md-24 text-center">
                                <button type="submit" class="btn btn-lg btn-primary">
                                    Обновить страницу
                                </button>
                            </div>
                        </div>

                    </form>
                </div> <!-- /.panel-body -->
            </div> <!-- /.panel-modal -->
        </div> <!-- /.auth-container -->
    </div> <!-- /.row -->
@endsection