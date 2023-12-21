@extends('admin.product.edit')

@section('product-title', 'Edit product - '. $basicProduct -> name)

@section('product-basic-form')
    @include('includes.profile.basicform')
@endsection