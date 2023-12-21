@extends('layouts.master')

@section('title', "Admin :: $title")

@section('content')
    <div class="row">
        <div class="col-sm-7 col-md-5 col-lg-5">
            @include('admin.sidebar')
        </div>

        <div class="col-sm-17 col-md-19 col-lg-19 animated fadeIn">
            @if($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_GOODS)
                @include('admin.components.goods.view')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_ORDERS)
                @include('admin.components.orders.view')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_CATEGORIES)
                @include('admin.components.categories.view')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_CITIES)
                @include('admin.components.cities.view')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_REGIONS)
                @include('admin.components.regions.view')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_SHOPS)
                @include('admin.components.shops.view')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_USERS)
                @include('admin.components.users.view')
            @endif
        </div>
    </div>
@endsection
