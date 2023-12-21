@extends('layouts.master')

@section('title', __('layout.Log in'))

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-10 col-md-offset-7 auth-container {{ $errors->count() > 0 ? ' animated shake' : '' }}">
            <div class="panel panel-modal">
                <div class="panel-heading">{{ __('login.Login verification') }}</div>
                <div class="panel-body">
                    @if (session('invalid_code'))
                        <div class="alert orange animated fadeIn">
                            <i class="fa fa-info-circle"></i> {{ __('login.Invalid code') }}
                        </div>
                    @endif

                    <form class="form-horizontal" role="form" method="POST" action="">
                        {{ csrf_field() }}
                        <p class="text-muted">{{ __('login.Enter otp code') }}</p>
                        <div class="form-group{{ $errors->has('code') ? ' has-error' : '' }}">
                            <div class="col-xs-24">
                                <input id="code" type="text" class="form-control" name="code" placeholder="{{ __('login.OTP code') }}" required {{ autofocus_on_desktop() }}>
                                @if ($errors->has('code'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('code') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

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
