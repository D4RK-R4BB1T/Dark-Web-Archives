@extends('layouts.master')

@section('title', __('admin.Adm dyn title', ['title' => $title]))

@section('content')
    <div class="row">
        <div class="col-sm-7 col-md-5 col-lg-5">
            @include('admin.sidebar')
        </div>

        <div class="col-sm-17 col-md-19 col-lg-19 animated fadeIn">
            @if($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_GOODS)
                @include('admin.components.goods.index')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_ORDERS)
                @include('admin.components.orders.index')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_CATEGORIES)
                @include('admin.components.categories.index')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_CITIES)
                @include('admin.components.cities.index')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_NEWS)
                @include('admin.components.news.index')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_REGIONS)
                @include('admin.components.regions.index')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_SHOPS)
                @include('admin.components.shops.index')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_USERS)
                @include('admin.components.users.index')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_TICKETS)
                @include('admin.components.tickets.index')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_ADVSTATS)
                @include('admin.components.advstats.index')
            @elseif($category === App\Http\Controllers\Admin\AdminController::ADMIN_CATEGORY_DISPUTES)
                @include('admin.components.disputes.index')
            @endif
        </div>
    </div>
@endsection

@section('header_scripts')
<link href="{{ asset('/assets/css/admin.css') }}" rel="stylesheet">
@endsection