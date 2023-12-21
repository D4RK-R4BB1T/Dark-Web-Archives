<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>

@extends('layouts.master')

@section('title', __('admin.Adding shop'))

@section('content')
    <div class="row">
        <div class="col-sm-7 col-md-5 col-lg-5">
            @include('admin.sidebar')
        </div>

        <div class="col-sm-17 col-md-19 col-lg-19 animated fadeIn">
            <form role="form" method="POST" action="{{ url($prefix . '/add_shops') }}">
                {{ csrf_field() }}

                <div class="form-group">
                    <label for="app_id">{{ __('admin.App id') }}</label>
                    <input class="form-control" id="app_id" name="app_id" />
                </div>

                <div class="form-group">
                    <label for="app_key">{{ __('admin.APP KEY') }}</label>
                    <input class="form-control" id="app_key" name="app_key" />
                </div>

                <div class="form-group">
                    <label for="url">{{ __('admin.url') }}</label>
                    <input class="form-control" id="url" name="url" />
                </div>

                <div class="form-group">
                    <label for="title">{{ __('admin.Title') }}</label>
                    <input class="form-control" id="title" name="title" />
                </div>

                <div class="form-group">
                    <label for="contacts_telegram">{{ __('layout.Image url') }}</label>
                    <input class="form-control" id="image_url" name="image_url" value="http://" />
                </div>

                <div class="form-group">
                    <label for="users_count">{{ __('layout.Users') }}</label>
                    <input class="form-control" id="users_count" name="users_count" type="number" min="0" max="2147483648" value="0">
                </div>

                <div class="form-group">
                    <label for="orders_count">{{ __('admin.Orders count') }}</label>
                    <input class="form-control" id="orders_count" name="orders_count" type="number" min="0" max="2147483648" value="0">
                </div>

                <div class="form-group">
                    <label for="rating">{{ __('layout.Rating') }}</label>
                    <input class="form-control" id="rating" name="rating" type="number" value="0.0" step="0.1">
                </div>

                <div class="form-group">
                    <input class="form-check-input" type="checkbox" id="enabled" name="enabled"> {{ mb_ucfirst(__('layout.enabled m')) }}
                </div>

                <div class="form-group">
                    <label for="plan">{{ __('admin.Plan') }}</label>
                    {{-- TODO --}}
                    <select class="form-control" id="plan" name="plan">
                        <option value="basic">{{ __('admin.Plan basic') }}</option>
                        <option value="advanced">{{ __('admin.Plan advanced') }}</option>
                        <option value="individual">{{ __('admin.Plan individual') }}</option>
                        <option value=""></option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('admin.Add') }}</button>
            </form>
        </div>
    </div>
@endsection