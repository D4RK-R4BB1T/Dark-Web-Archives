<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>

@extends('layouts.master')

@section('title', __('admin.Adding user'))

@section('content')
    <div class="row">
        <div class="col-sm-7 col-md-5 col-lg-5">
            @include('admin.sidebar')
        </div>

        <div class="col-sm-17 col-md-19 col-lg-19 animated fadeIn">
            <form role="form" method="POST" action="{{ url($prefix . '/users/add') }}">
                {{ csrf_field() }}

                <div class="form-group">
                    <label for="username">{{ __('layout.Username') }}</label>
                    <input class="form-control" id="username" name="username" />
                </div>

                <div class="form-group">
                    <label for="password">{{ __('layout.Password') }}</label>
                    <input class="form-control" id="password" name="password" type="password" />
                </div>

                <div class="form-group">
                    <label for="contacts_other">{{ __('layout.Contacts other') }}</label>
                    <input class="form-control" id="contacts_other" name="contacts_other" />
                </div>

                <div class="form-group">
                    <label for="contacts_jabber">Jabber</label>
                    <input class="form-control" id="contacts_jabber" name="contacts_jabber" />
                </div>

                <div class="form-group">
                    <label for="contacts_telegram">Telegram</label>
                    <input class="form-control" id="contacts_telegram" name="contacts_telegram" />
                </div>

                {{--<div class="form-group">
                    <label for="role">{{ __('admin.Role') }}</label>
                    <select class="form-control" id="role" name="role">
                        <option value="admin">{{ __('admin.Admin') }}</option>
                        <option value="user" selected="selected">{{ __('admin.User') }}</option>
                    </select>
                </div>--}}

                <div class="form-group">
                    <input class="form-check-input" type="checkbox" id="active" name="active"> {{ __('admin.Active') }}
                </div>

                <div class="form-group">
                    <label for="buy_count">{{ __('admin.Buy count') }}</label>
                    <input class="form-control" id="buy_count" name="buy_count" type="number" min="0" max="2147483648" value="0">
                </div>

                <div class="form-group">
                    <label for="buy_sum">{{ __('admin.Buy sum') }}</label>
                    <input class="form-control" id="buy_sum" name="buy_sum" type="number" min="0" max="65535" value="0" step="0.1">
                </div>

                <button type="submit" class="btn btn-primary">{{ __('admin.Add') }}</button>
            </form>
        </div>
    </div>
@endsection