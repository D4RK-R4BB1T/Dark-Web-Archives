{{--
This file is part of MM2 project. 
Description: Shows when account is created and some background tasks are performing (like Bitcoin wallet creation)
--}}
@extends('layouts.master')

@section('title', __('layout.Creating account'))

@section('header_scripts')
    <meta http-equiv="refresh" content="3; URL=/auth/pending">
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-10 col-md-offset-7 auth-container">
            <div class="panel panel-modal">
                <div class="panel-heading">{{ __('layout.Creating account') }}</div>
                <div class="panel-body">
                    <p class="text-center">
                        {{ __('layout.Setting up account') }}
                        <br />
                        <br />
                        <img src="/assets/img/select2-spinner.gif" />
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection