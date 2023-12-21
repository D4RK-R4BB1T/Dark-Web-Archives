@extends('layouts.master')

@section('title', 'Регистрация')

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-10 col-md-offset-7 auth-container {{ $errors->count() > 0 ? ' animated shake' : '' }}">
            <div class="panel panel-modal">
                <div class="panel-heading">{{ config('catalog.header_title') }}: {{ __('layout.Register') }}</div>
                <div class="panel-body">
                    <form class="form-horizontal" role="form" method="POST" action="{{ url('/auth/register') }}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('username') ? ' has-error' : '' }}">
                            <div class="col-xs-24">
                                <input id="username" type="text" class="form-control" name="username"
                                       placeholder="{{ __('layout.Username') }}" value="{{ old('username') }}" required {{ autofocus_on_desktop() }}>

                                @if ($errors->has('username'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('username') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                            <div class="col-md-24">
                                <input id="password" type="password" class="form-control" name="password"
                                       placeholder="{{ __('layout.Password') }}" required>

                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
                            <div class="col-md-24">
                                <input id="password_confirmation" type="password" class="form-control"
                                       name="password_confirmation" placeholder="{{ __('login.Confirm password') }}" required>

                                @if ($errors->has('password_confirmation'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password_confirmation') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('captcha') ? ' has-error' : '' }}">
                            <div class="col-md-24">
                                <div class="text-center" style="margin-bottom: 10px">
                                    {!! captcha_img() !!}
                                </div>
                                <input id="captcha" type="text" class="form-control"
                                       name="captcha" placeholder="{{ __('login.Enter code from the image') }}" required>

                                @if ($errors->has('captcha'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('captcha') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group {{ $errors->has('terms') ? 'has-error' : '' }}">
                            <div class="col-md-24 text-center">
                                <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
                                <label for="terms" style="font-weight: normal">
                                    Я согласен с
                                    <a href="#">
                                        @component('layouts.components.component-modal-toggle', ['id' => 'terms'])
                                            <strong>правилами каталога</strong>
                                        @endcomponent
                                    </a>
                                </label>
                            </div>

                            @if ($errors->has('terms'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('terms') }}</strong>
                                </span>
                            @endif
                        </div>

                        {{--<div class="form-group{{ $errors->has('role') ? ' has-error' : '' }}">--}}
                            {{--<div class="col-md-24 text-center">--}}
                                {{--<div class="radio-inline">--}}
                                    {{--<label>--}}
                                        {{--<input id="role-user" type="radio" name="role"--}}
                                               {{--value="{{ \App\User::ROLE_USER }}"--}}
                                               {{--@if(!old('role') || old('role') === \App\User::ROLE_USER) checked @endif>--}}
                                        {{--Покупатель--}}
                                    {{--</label>--}}
                                {{--</div>--}}
                                {{--<div class="radio-inline">--}}
                                    {{--<label>--}}
                                        {{--<input id="role-shop" type="radio" name="role"--}}
                                               {{--value="{{ \App\User::ROLE_SHOP_PENDING }}"--}}
                                               {{--@if(old('role') === \App\User::ROLE_SHOP_PENDING) checked @endif>--}}
                                        {{--Продавец--}}
                                    {{--</label>--}}
                                {{--</div>--}}
                                {{--@if ($errors->has('role'))--}}
                                    {{--<span class="help-block">--}}
                                        {{--<strong>{{ $errors->first('role') }}</strong>--}}
                                    {{--</span>--}}
                                {{--@endif--}}
                            {{--</div>--}}
                        {{--</div>--}}

                        <div class="form-group">
                            <div class="col-md-24">
                                <button type="submit" class="btn btn-lg btn-primary col-xs-24">
                                    {{ __('layout.Register') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div> <!-- /.panel-body -->
            </div> <!-- /.panel -->
        </div> <!-- /.auth-container -->
    </div> <!-- /.row -->
@endsection

@section('modals')
    @include('auth.components.modals.rules', ['modal_lg' => true])
@endsection