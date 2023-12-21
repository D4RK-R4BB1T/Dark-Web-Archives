{{--
This file is part of MM2-dev project.
Description: Employees add page
--}}
@extends('layouts.master')

@section('title', 'Добавить сотрудника :: Сотрудники')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.employees.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Добавление сотрудника</h3>
                <hr class="small" />
                <form action="" method="post">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-xs-20 col-xs-offset-2">
                            <div class="form-group{{ $errors->has('username') ? ' has-error' : '' }}">
                                <input id="username" type="text" class="form-control" name="username" placeholder="Введите логин пользователя" value="{{ old('username') }}" required {{ autofocus_on_desktop() }}>
                                @if ($errors->has('username'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('username') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Пользователю будет отправлено приглашение присоединиться к магазину.
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Добавить сотрудника</button>
                    </div>
                </form>
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->

        {{--<div class="col-sm-6 animated fadeIn">--}}
            {{--@include('shop.management.components.block-employees-reminder')--}}
        {{--</div> <!-- /.col-sm-6 -->--}}
    </div> <!-- /.row -->
@endsection