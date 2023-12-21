{{--
This file is part of MM2-dev project.
Description: Settings security page
--}}
@extends('layouts.master')

@section('title', 'Пароль и безопасность :: Настройки')

@section('content')
    <div class="row">
        <div class="col-sm-6 col-md-6 col-lg-5">
            @include('settings.sidebar')
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-12 col-md-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Изменить пароль</h3>
                <hr class="small" />
                <form action="" method="post">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-xs-16 col-xs-offset-4">
                            <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                                <input id="password" type="password" class="form-control" name="password" placeholder="Текущий пароль" required {{ autofocus_on_desktop() }}>
                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-16 col-xs-offset-4">
                            <div class="form-group{{ $errors->has('new_password') ? ' has-error' : '' }}">
                                <input id="new_password" type="password" class="form-control" name="new_password" placeholder="Новый пароль" required>
                                @if ($errors->has('new_password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('new_password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-16 col-xs-offset-4">
                            <div class="form-group{{ $errors->has('new_password_confirmation') ? ' has-error' : '' }}">
                                <input id="new_password_confirmation" type="password" class="form-control" name="new_password_confirmation" placeholder="Новый пароль (подтверждение)" required>
                                @if ($errors->has('new_password_confirmation'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('new_password_confirmation') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Изменить пароль</button>
                    </div>
                </form>
            </div> <!-- /.col-sm-13 -->

            <div class="well block">
                <h3>Двухфакторная авторизация</h3>
                <hr class="small" />
                <p>
                    @if (Auth::user()->totp_key || Auth::user()->pgp_key)
                        <strong class="text-success"><i class="glyphicon glyphicon-ok"></i></strong>&nbsp; Двухфакторная авторизация <span class="text-success">включена</span>.
                    @else
                        <span class="text-danger"><i class="glyphicon glyphicon-remove"></i></span>&nbsp; Двухфакторная авторизация <span class="text-danger">отключена</span>.
                    @endif
                </p>
                <hr class="small" />
                <div class="text-center">
                    @if (Auth::user()->totp_key)
                        <a class="btn btn-orange" href="/settings/security/2fa/otp/disable">Отключить</a>
                    @elseif (Auth::user()->pgp_key)
                        <a class="btn btn-orange" href="/settings/security/2fa/pgp/disable">Отключить</a>
                    @else
                        @component('layouts.components.component-modal-toggle', ['id' => '2fa', 'class' => 'btn btn-orange'])
                            Подключить
                        @endcomponent
                    @endif
                </div>
            </div>
        </div>

        <div class="col-sm-6 animated fadeIn">
            @include('settings.components.block-security-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection

@section('modals')
    @if (!Auth::user()->totp_key && !Auth::user()->pgp_key)
        @include('settings.components.modals.2fa')
    @endif
@endsection
