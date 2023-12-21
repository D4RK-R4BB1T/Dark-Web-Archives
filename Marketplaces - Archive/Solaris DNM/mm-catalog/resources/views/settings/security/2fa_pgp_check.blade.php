{{--
This file is part of MM2-dev project.
Description: 2FA OTP enable security page
--}}
@extends('layouts.master')

@section('title', 'Двухфакторная авторизация :: Пароль и безопасность :: Настройки')

@section('content')
    <div class="row">
        <div class="col-sm-6 col-md-6 col-lg-5">
            @include('settings.sidebar')
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-12 col-md-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Двухфакторная авторизация</h3>
                <hr class="small" />
                <form action="/settings/security/2fa/pgp/check" method="post">
                    {{ csrf_field() }}
                    Расшифруйте сообщение и введите результат в поле ниже: <br />
                    <pre><code style="font-weight: normal">{{ $message }}</code></pre>
                    <div class="form-group{{ $errors->has('code') ? ' has-error' : '' }}">
                        <input id="code" type="text" class="form-control" name="code" placeholder="Введите расшифрованное сообщение" required {{ autofocus_on_desktop() }}>
                        @if ($errors->has('code'))
                            <span class="help-block">
                                <strong>{{ $errors->first('code') }}</strong>
                            </span>
                        @endif
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Сохранить</button>
                        &nbsp;
                        <a class="text-muted" href="{{ URL::previous() }}">вернуться назад</a>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-13 -->

        <div class="col-sm-6 animated fadeIn">
            @include('settings.components.block-security-2fa-otp')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection