<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>

@extends('layouts.master')

@section('title', __('admin.Adding news'))

@section('content')
    <div class="row">
        <div class="col-sm-7 col-md-5 col-lg-5">
            @include('admin.sidebar')
        </div>

        <div class="col-sm-17 col-md-19 col-lg-19 animated fadeIn">
            <form role="form" method="POST" action="{{ url($prefix . '/news/store') }}">
                {{ csrf_field() }}
                <div class="form-group">
                    <label for="title">{{ __('admin.Title') }}</label>
                    <input class="form-control" id="title" name="title" />
                </div>

                <div class="form-group">
                    <label for="text">{{ __('admin.News text') }}</label>
                    <textarea class="form-control" id="text" name="text" rows="6"></textarea>
                </div>

                <div class="form-group">
                    <label for="author">{{ __('admin.Author') }}</label>
                    <input class="form-control" id="author" name="author" />
                </div>

                <button type="submit" class="btn btn-primary">{{ __('admin.Add') }}</button>
            </form>
        </div>
    </div>
@endsection