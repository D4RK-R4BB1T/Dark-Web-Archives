<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>

@extends('layouts.master')

@section('title', __('admin.Adding goods'))

@section('content')
    <div class="row">
        <div class="col-sm-7 col-md-5 col-lg-5">
            @include('admin.sidebar')
        </div>

        <div class="col-sm-17 col-md-19 col-lg-19 animated fadeIn">
            <form role="form" method="POST" action="{{ url($prefix . '/goods/store') }}">
            {{ csrf_field() }}

                <div class="form-group">
                    <label for="title">{{ __('admin.Title') }}</label>
                    <input class="form-control" id="title" name="title" />
                </div>

                <div class="form-group">
                    <label for="category_id">{{ __('layout.Category') }}</label>
                    <select class="form-control" id="category_id" name="category_id">
                        @foreach ($categories_main as $cat)
                            <optgroup label="{{ $cat->title }}">
                                @foreach ($categories_children as $child)
                                    @if ($child->parent_id == $cat->id)
                                        <option value="{{ $child->id }}">{{ $child->title }}</option>
                                    @endif
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="city_id">{{ __('layout.City') }}</label>
                    <select class="form-control" id="city_id" name="city_id">
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">{{ __('layout.Description') }}</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="image_url">{{ __('layout.Image url') }}</label>
                    <input class="form-control" id="image_url" name="image_url" />
                </div>

                <div class="form-group">
                    <input class="form-check-input" type="checkbox" id="has_quests" name="has_quests"> {{ __('admin.Has quests') }}
                </div>

                <div class="form-group">
                    <input class="form-check-input" type="checkbox" id="has_ready_quests" name="has_ready_quests"> {{ __('admin.Has instant quests') }}
                </div>

                <div class="form-group">
                    <label for="buy_count">{{ __('admin.Buy count') }}</label>
                    <input class="form-control" id="buy_count" name="buy_count" type="number" min="0" max="2147483648" value="0">
                </div>

                <div class="form-group">
                    <label for="reviews_count">{{ __('admin.Review count') }}</label>
                    <input class="form-control" id="reviews_count" name="reviews_count" type="number" min="0" max="2147483648" value="0">
                </div>

                <div class="form-group">
                    <label for="rating">{{ __('layout.Rating') }}</label>
                    <input class="form-control" id="rating" name="rating" type="number" step="0.1" value="0.0">
                </div>

                <button type="submit" class="btn btn-primary">{{ __('admin.Add') }}</button>
            </form>
        </div>
    </div>
@endsection