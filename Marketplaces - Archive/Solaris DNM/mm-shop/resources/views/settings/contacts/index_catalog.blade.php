{{--
This file is part of MM2-dev project.
Description: Settings contacts page
--}}
@extends('layouts.master')

@section('title', 'Контакты :: Настройки')

@section('content')
    <div class="row">
        <div class="col-sm-6 col-md-6 col-lg-5">
            @include('settings.sidebar')
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-12 col-md-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Контакты</h3>
                <hr class="small" />
                <div class="alert alert-info" style="margin-bottom: 0">
                    Ваш аккаунт относится к типу общего аккаунта. Изменение данных настроек вы можете произвести на странице настроек в каталоге Solaris.
                </div>
            </div> <!-- /.col-sm-13 -->
        </div>

        <div class="col-sm-6 animated fadeIn">
            @include('settings.components.block-security-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection