@extends('includes.profile.addingform')

@section('form-content')
<h3>Add image</h3>
<hr>
<form action="{{ route('profile.vendor.product.images.post', $basicProduct) }}" method="POST" enctype="multipart/form-data">
    {{ csrf_field() }}
    <div class="form-group">
        <input type="file" class="form-control border-0" name="picture" id="picture">
    </div>
    <div class="form-inline">
        <div class="form-check mx-2 mb-2 ">
            <input class="form-check-input" type="checkbox" value="1" name="first" id="defaultcheck">
            <label class="form-check-label" for="defaultcheck">
                Default product image
            </label>
        </div>

        <button type="submit" class="btn btn-primary mb-2">Add image</button>
    </div>
</form>

{{-- Images --}}

<h3 class="mt-3">Images of the product</h3>
<hr>
<p class="text-muted">Default picture is marked with green borders.</p>
@if(!empty($productsImages ?? []))
<div class="card-columns">
    @foreach($productsImages as $image)
        <div class="card my-3 p-2 @if($image -> first) bg-success @endif">
            <img class="card-img" src="{{ asset('storage/' . $image -> image) }}" alt="Product image">
            <div class="card-img-overlay text-center">
                @if(!$image -> first)
                    <a href="{{ route('profile.vendor.product.images.default', $image -> id) }}" class="btn btn-sm btn-primary">Default</a>
                    <a href="{{ route('profile.vendor.product.images.remove', $image -> id) }}" class="btn btn-sm btn-danger"><i class="far fa-trash-alt"></i></a>
                @else
                    <p class="bg-white text-muted">Default picture</p>
                @endif
            </div>
        </div>
    @endforeach
</div>
@else
    <div class="col-12 text-center alert alert-warning">
        You don't have any images added, it must be at least one!
    </div>
@endif

@stop