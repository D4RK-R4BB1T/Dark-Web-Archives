@extends('master.profile')

@section('title', 'Products')

@section('profile-content')
    @include('includes.flash.success')
    @include('includes.flash.error')
    @include('includes.validation')
    <h1 class="mb-3">
        @yield('product-title')
    </h1>
    <hr>
    @vendor
    <div class="accordion mb-3" id="accordionExample">
        <div class="card">
            <div class="card-header" id="headingOne">
                @if(request() -> is('profile/vendor/product/edit/*'))
                    <a class="btn" href="{{ route('profile.vendor.product.edit', $basicProduct) }}">
                @else
                    <a class="btn" href="{{ route('profile.vendor.product.add', session('product_type')) }}">
                @endif
                    <h3>
                        <i class="fas fa-info-circle"></i>
                        Basic information
                    </h3>
                </a>
            </div>

            @yield('product-basic-form')
        </div>
        <div class="card">
            <div class="card-header">
                @if(request() -> is('profile/vendor/product/edit/*'))
                    <a class="btn" href="{{ route('profile.vendor.product.edit', [$basicProduct, 'offers']) }}">
                @else
                    <a class="btn" href="{{ route('profile.vendor.product.offers') }}">
                @endif
                    <h3>
                        <i class="fas fa-money-check-alt"></i>
                        Price and offers
                    </h3>
                </a>
            </div>

            @yield('product-offers-form')
        </div>
        @php
            if(empty($type))
                $type = session('product_type');
        @endphp
        @if($type == 'physical')
            <div class="card">
                <div class="card-header">
                    @if(request() -> is('profile/vendor/product/edit/*'))
                        <a class="btn" href="{{ route('profile.vendor.product.edit', [$basicProduct, 'delivery']) }}">
                    @else
                        <a class="btn" href="{{ route('profile.vendor.product.delivery') }}">
                    @endif
                        <h3>
                            <i class="fas fa-truck"></i>
                            Delivery options
                        </h3>
                    </a>
                </div>

                @yield('product-delivery-form')
            </div>
        @else
            <div class="card">
                <div class="card-header">
                @if(request() -> is('profile/vendor/product/edit/*'))
                    <a class="btn" href="{{ route('profile.vendor.product.edit', [$basicProduct, 'digital']) }}">
                @else
                    <a class="btn" href="{{ route('profile.vendor.product.digital') }}">
                @endif
                        <h3>
                            <i class="fas fa-desktop"></i>
                            Digital options
                        </h3>
                    </a>
                </div>

                @yield('product-digital-form')
            </div>
        @endif
        <div class="card">
            <div class="card-header">
            @if(request() -> is('profile/vendor/product/edit/*'))
                <a class="btn" href="{{ route('profile.vendor.product.edit', [$basicProduct, 'images']) }}">
            @else
                <a class="btn" href="{{ route('profile.vendor.product.images') }}">
            @endif
                    <h3>
                        <i class="fas fa-images"></i>
                        Images
                    </h3>
                </a>
            </div>

            @yield('product-images-form')
        </div>
    </div>

    @if(!request() -> is('profile/vendor/product/edit/*'))
        <form method="POST" action="{{ route("profile.vendor.product.post") }}" class="text-center my-2">
            {{ csrf_field() }}
            <button class="btn btn-lg btn-block btn-success"><i class="far fa-plus-square mr-2"></i> Add product</button>
        </form>
    @endif

    @endvendor
@stop