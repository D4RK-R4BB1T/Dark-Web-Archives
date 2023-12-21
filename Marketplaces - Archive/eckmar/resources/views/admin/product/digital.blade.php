@extends('admin.product.edit')

@section('product-title', 'Add ' . session('product_type') . ' product')

@section('product-digital-form')
    @include('includes.profile.digitalform')
@endsection