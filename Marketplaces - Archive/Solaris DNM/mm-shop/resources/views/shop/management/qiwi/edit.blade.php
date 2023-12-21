{{--
This file is part of MM2-dev project.
Description: Qiwi wallet add
--}}
@extends('layouts.master')

@section('title', 'Добавление QIWI-кошелька')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.qiwi.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Редактирование QIWI-кошелька</h3>
                <hr class="small" />
                <form action="" role="form" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group{{ $errors->has('login') ? ' has-error' : '' }}">
                                <input id="login" type="text" class="form-control" name="login" placeholder="Номер кошелька (11 цифр)" value="{{ old('login') ?: $qiwiWallet->login }}" required {{ autofocus_on_desktop() }}>
                                @if ($errors->has('login'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('login') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">Введите номер без +, например 79110000000</span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-8 -->
                        <div class="col-xs-12">
                            <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                                <input id="password" type="password" class="form-control" name="password" placeholder="Пароль" value="">

                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-8 -->
                    </div> <!-- /.row -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group{{ $errors->has('daily_limit') ? ' has-error' : '' }}">
                                <input id="daily_limit" type="text" class="form-control" name="daily_limit" placeholder="Дневной лимит" value="{{ old('daily_limit') ?: $qiwiWallet->daily_limit }}" required>

                                @if ($errors->has('daily_limit'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('daily_limit') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Кошелек не будет принимать платежи, если за текущий день на него поступит больше данной суммы. Используйте 0 для отключения лимита.
                                    </span>
                                @endif
                            </div> <!-- /.col-xs-8 -->
                        </div>
                        <div class="col-xs-12">
                            <div class="form-group{{ $errors->has('monthly_limit') ? ' has-error' : '' }}">
                                <input id="monthly_limit" type="text" class="form-control" name="monthly_limit" placeholder="Месячный лимит" value="{{ old('monthly_limit') ?: $qiwiWallet->monthly_limit }}" required>

                                @if ($errors->has('monthly_limit'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('monthly_limit') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Кошелек не будет принимать платежи, если за текущий месяц на него поступит больше данной суммы. Используйте 0 для отключения лимита.
                                    </span>
                                @endif
                            </div> <!-- /.col-xs-8 -->
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Отредактировать кошелек</button>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-qiwi-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection