@extends('layouts.master')

@section('title', 'Вход')

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-10 col-md-offset-7 auth-container {{ $errors->count() > 0 ? ' animated shake' : '' }}">
            <div class="panel panel-modal">
                <div class="panel-heading">{{ config('catalog.application_title') }}: {{ __('layout.Log in') }}</div>
                <div class="panel-body">
                    @if (session('login_required'))
                        <div class="alert orange animated fadeIn">
                            <i class="fa fa-info-circle"></i> {{ __('login.Need to login') }}
                        </div>
                    @endif

                    @if (session('logout'))
                        <div class="alert alert-success animated fadeIn">
                            <i class="fa fa-info-circle"></i> {{ __('login.Successfully out') }}
                        </div>
                    @endif

                    <form class="form-horizontal" role="form" method="POST" action="{{ url('/auth/login') }}">
                        {{ csrf_field() }}
                        <input type="hidden" name="redirect_to" value="{{ request()->get('redirect_to') }}">
                        <input type="hidden" name="redirect_after_login" value="{{ request()->get('redirect_after_login') }}">
                        <div class="form-group{{ $errors->has('username') ? ' has-error' : '' }}">
                            <div class="col-xs-24">
                                <input id="username" type="text" class="form-control" name="username" placeholder="{{ __('layout.Username') }}" value="{{ old('username') }}" required {{ !old('username') ? autofocus_on_desktop() : '' }}>

                                @if ($errors->has('username'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('username') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                            <div class="col-md-24">
                                <input id="password" type="password" class="form-control" name="password" placeholder="{{ __('layout.Password') }}" required {{ old('username') ? autofocus_on_desktop() : '' }}>

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
                                        {!! captcha_img() !!}
                                    </div>

                                    <input id="captcha" type="text" class="form-control" name="captcha" placeholder="{{ __('layout.Enter code from the image') }}" required>

                                    @if ($captchaTypedWrong)
                                        <span class="help-block">
                                            <strong>{{ $errors->first('captcha') }}</strong>
                                        </span>
                                    @else
                                        <span class="help-block">
                                            {{ __('login.Need captcha') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="form-group">
                            <div class="col-md-24">
                                <button type="submit" class="btn btn-lg btn-primary col-xs-24">
                                    {{ __('layout.Log in') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div> <!-- /.panel-body -->
            </div> <!-- /.panel -->
        </div> <!-- /.auth-container -->
    </div> <!-- /.row -->
@endsection
