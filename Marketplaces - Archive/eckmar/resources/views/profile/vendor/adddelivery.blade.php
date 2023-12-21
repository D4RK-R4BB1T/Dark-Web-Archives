@extends('master.productadding')

@if(request() -> is('profile/vendor/product/edit/*'))
    @section('product-title', 'Edit product - '. $physicalProduct -> product -> name)
@else
    @section('product-title', 'Add ' . session('product_type') . ' product')
@endif

@section('product-delivery-form')
    @include('includes.profile.deliveryform')
@endsection