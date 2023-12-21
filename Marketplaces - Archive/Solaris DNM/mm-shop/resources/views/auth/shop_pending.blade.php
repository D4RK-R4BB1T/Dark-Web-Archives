{{-- 
This file is part of MM2 project. 
Description: Shows when shop awaiting confirmations for the payment.
--}}
@extends('layouts.master')

@section('title', 'Создание аккаунта')

@section('header_scripts')
    <meta http-equiv="refresh" content="60; URL=/">
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-10 col-md-offset-7 auth-container">
            <div class="panel panel-modal">
                <div class="panel-heading">Создание аккаунта</div>
                <div class="panel-body">
                    <p class="text-center">
                        Мы ожидаем подтверждений вашей транзакции. <br />Заходите через несколько минут.
                        <br />
                        <br />
                        <img src="{{ url('/assets/img/select2-spinner.gif') }}" />
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection