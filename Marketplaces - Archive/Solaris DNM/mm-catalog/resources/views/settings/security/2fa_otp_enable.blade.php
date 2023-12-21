{{--
This file is part of MM2-dev project.
Description: 2FA OTP enable security page
--}}
@extends('layouts.master')

@section('title', __('layout.Two-fa section title'))

@section('content')
    <div class="row">
        <div class="col-sm-6 col-md-6 col-lg-5">
            @include('settings.sidebar')
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-12 col-md-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>{{ __('layout.Two-fa') }}</h3>
                <hr class="small" style="margin-bottom: 0" />
                <div class="row">
                    <div class="col-md-20 col-md-offset-2 text-center">
                        <img src="{{ $image }}" /><br />
                        <span style="font-size: 14px; font-weight: bold">{{ $totpKey }}</span><br /><br />
                        <span class="text-muted">{{ __('layout.Two-fa enable code') }}</span>
                    </div>
                </div>
                <br />
                <form action="" method="post">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-xs-16 col-xs-offset-4">
                            <div class="form-group{{ $errors->has('code') ? ' has-error' : '' }}">
                                <input id="code" type="text" class="form-control" name="code" placeholder="{{ __('layout.Enter code') }}" required {{ autofocus_on_desktop() }}>
                                @if ($errors->has('code'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('code') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">{{ __('layout.Save') }}</button>
                        &nbsp;
                        <a class="text-muted" href="{{ URL::previous() }}">{{ __('layout.Go back') }}</a>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-13 -->

        <div class="col-sm-6 animated fadeIn">
            @include('settings.components.block-security-2fa-otp')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection