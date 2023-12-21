@extends('master.admin')

@section('title', 'Editing Products')

@section('admin-content')
    @include('includes.flash.success')
    @include('includes.flash.error')
    @include('includes.validation')
    <h1 class="mb-3">
        Editing product: {{ $basicProduct -> name }}
    </h1>
    <hr>
    <div class="accordion mb-3" id="accordionExample">
        <div class="card">
            <div class="card-header" id="headingOne">
                <a class="btn" href="{{ route('admin.product.edit', $basicProduct) }}">
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
                <a class="btn" href="{{ route('admin.product.edit', [$basicProduct, 'offers']) }}">
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
                    <a class="btn" href="{{ route('admin.product.edit', [$basicProduct, 'delivery']) }}">
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
                    <a class="btn" href="{{ route('admin.product.edit', [$basicProduct, 'digital']) }}">
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
                <a class="btn" href="{{ route('admin.product.edit', [$basicProduct, 'images']) }}">
                    <h3>
                        <i class="fas fa-images"></i>
                        Images
                    </h3>
                </a>
            </div>

            @yield('product-images-form')
        </div>
    </div>


@stop