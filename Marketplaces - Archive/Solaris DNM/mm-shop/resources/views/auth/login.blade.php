@extends('layouts.master')

@section('title', 'Войти')

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-10 col-md-offset-7 auth-container {{ $errors->count() > 0 ? ' animated shake' : '' }}">
            <div class="panel panel-modal">
                <div class="panel-heading">{{ config('mm2.header_title') }}: Вход</div>
                <div class="panel-body">
                    @if (session('login_required'))
                        <div class="alert orange animated fadeIn">
                            <i class="fa fa-info-circle"></i> Для просмотра запрашиваемой страницы необходимо авторизоваться.
                        </div>
                    @endif

                    @if (session('logout'))
                        <div class="alert alert-success animated fadeIn">
                            <i class="fa fa-info-circle"></i> Вы успешно вышли из системы.
                        </div>
                    @endif

                    <form class="form-horizontal" role="form" method="POST" action="{{ url('/auth/login') }}">
                        {{ csrf_field() }}
                        <input type="hidden" name="catalog_login" value="true">
                        <hr class="small" />
                        <div class="text-center">
                            <span class="text-muted">Общий аккаунт Solaris позволяет синхронизировать покупки из разных магазинов в одном месте и входить в любой магазин без регистрации.</span>
                            <br /><br />
                            <input type="submit" class="btn btn-primary" value="Войти используя аккаунт Solaris">
                        </div>
                        <hr />
                    </form>

                    <form class="form-horizontal" role="form" method="POST" action="{{ url('/auth/login') }}">
                        {{ csrf_field() }}
                        <div class="form-group{{ $errors->has('username') ? ' has-error' : '' }}">
                            <div class="col-xs-24">
                                <input id="username" type="username" class="form-control" name="username" placeholder="Имя пользователя" value="{{ old('username') }}" required {{ !old('username') ? autofocus_on_desktop() : '' }}>

                                @if ($errors->has('username'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('username') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                            <div class="col-md-24">
                                <input id="password" type="password" class="form-control" name="password" placeholder="Пароль" required {{ old('username') ? autofocus_on_desktop() : '' }}>

                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if ($errors->has('captcha'))
                            <?php
                                $captchaTypedWrong = $errors->first('captcha', null) === trans('validation.captcha');
                            ?>
                            <div class="form-group{{ $captchaTypedWrong ? ' has-error' : '' }}">
                                <div class="col-md-24">
                                    <div class="text-center" style="margin-bottom: 10px">
                                        <img src="{{ url(\App\Packages\Captcha::url()) }}" />
                                    </div>

                                    <input id="captcha" type="text" class="form-control" name="captcha" placeholder="Введите код с картинки" required>

                                    @if ($captchaTypedWrong)
                                        <span class="help-block">
                                            <strong>{{ $errors->first('captcha') }}</strong>
                                        </span>
                                    @else
                                        <span class="help-block">
                                            Для входа в данный аккаунт необходимо ввести код с картинки.
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="form-group">
                            <div class="col-md-24">
                                <button type="submit" class="btn btn-lg btn-primary col-xs-24">
                                    Войти
                                </button>
                            </div>
                        </div>
                    </form>
                </div> <!-- /.panel-body -->
            </div> <!-- /.panel -->
        </div> <!-- /.auth-container -->
    </div> <!-- /.row -->
@endsection
