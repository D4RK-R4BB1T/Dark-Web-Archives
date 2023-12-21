@extends('layouts.master')

@section('title', "Admin :: $title")

@section('content')
    <div class="row">
        <div class="col-sm-7 col-md-5 col-lg-5">
            @include('admin.sidebar')
        </div>

        <div class="col-sm-17 col-md-19 col-lg-19 animated fadeIn">
            @if($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_GOODS)
                @include('admin.components.goods.edit')
{{--            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_ORDERS)--}}
{{--                @include('admin.components.orders.edit')--}}
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_CATEGORIES)
                @include('admin.components.categories.edit')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_CITIES)
                @include('admin.components.cities.edit')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_NEWS)
                @include('admin.components.news.edit')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_REGIONS)
                @include('admin.components.regions.edit')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_SHOPS)
                @include('admin.components.shops.edit')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_USERS)
                @php
                    $act = isset($act) ? $act : 'users.edit';
                @endphp
                @include("admin.components.$act")
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_ADVSTATS)
                @include('admin.components.advstats.edit')
            @endif
        </div>
    </div>
@endsection
