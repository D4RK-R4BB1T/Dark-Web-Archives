@extends('master.productadding')

@if(request() -> is('profile/vendor/product/edit/*'))
    @section('product-title', 'Edit product - '. $basicProduct -> name)
@else
    @section('product-title', 'Add ' . $type . ' product')
@endif

@section('product-basic-form')
    @include('includes.profile.basicform')
@endsection