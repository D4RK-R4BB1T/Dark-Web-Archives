@extends('master.confirmation')

@section('confirmation-title', 'Delete ' . $product -> name)

@section('confirmation-content')
    This action can't be undone! Confirm permanently deleting product <strong>{{ $product -> name }}</strong>? All products offers, delivery options and images will be permanently deleted as well!
@endsection

@section('confirmation-back', route('profile.vendor'))
@section('confirmation-next', route('profile.vendor.product.remove', $product -> id))