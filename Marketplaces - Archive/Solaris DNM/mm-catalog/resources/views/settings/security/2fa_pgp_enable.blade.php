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
                <form action="" method="post">
                    {{ csrf_field() }}
                    Введите ваш публичный PGP-ключ. Он должен иметь следующий формат: <br />
                    <pre>-----BEGIN PGP PUBLIC KEY BLOCK-----
.........................
-----END PGP PUBLIC KEY BLOCK-----</pre>
                    <div class="form-group{{ $errors->has('pgp_key') ? ' has-error' : '' }}">
                        <textarea rows="8" id="pgp_key" type="text" class="form-control" name="pgp_key" placeholder="Введите ваш публичный PGP-ключ" required {{ autofocus_on_desktop() }}></textarea>
                        @if ($errors->has('pgp_key'))
                            <span class="help-block">
                                <strong>{{ $errors->first('pgp_key') }}</strong>
                            </span>
                        @endif
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Сохранить</button>
                        &nbsp;
                        <a class="text-muted" href="/settings/security">вернуться назад</a>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-13 -->

        <div class="col-sm-6 animated fadeIn">
            @include('settings.components.block-security-2fa-otp')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection