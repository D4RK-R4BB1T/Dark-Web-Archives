<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>

@extends('layouts.master')

@section('title', __('admin.Adding category'))

@section('content')
<div class="row">
    <div class="col-sm-7 col-md-5 col-lg-5">
        @include('admin.sidebar')
    </div>

    <div class="col-sm-17 col-md-19 col-lg-19 animated fadeIn">
        <form role="form" method="POST" action="{{ url($prefix . '/categories/store') }}">
            {{ csrf_field() }}
            <div class="form-group">
                <label for="title">{{ __('admin.Title') }}</label>
                <input class="form-control" id="title" name="title" />
            </div>

            <div class="form-group">
                <label for="parent_id">{{ __('admin.Parent category') }}</label>
                <select class="form-control" id="parent_id" name="parent_id">
                    <option value="" selected="selected">-</option>
                    @foreach($categories_main as $cm)
                        <option value="{{ $cm->id }}">{{ $cm->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="priority">{{ __('admin.Priority') }}</label>
                <input class="form-control" id="priority" name="priority" type="number" min="-2147483648" max="2147483648" value="0">
            </div>

            <button type="submit" class="btn btn-primary">{{ __('admin.Add') }}</button>
        </form>
    </div>
</div>
@endsection