@extends('includes.profile.addingform')

@section('form-content')
<h3>Add digital content</h3>
<hr>
<form method="POST" action="{{ route('profile.vendor.product.digital.post', $digitalProduct) }}">
    {{ csrf_field() }}

    <div class="form-group">
        <label for="product_content">Product's content:</label>
        <textarea name="product_content" id="product_content"
            class="form-control @error('content', $errors) is-invalid @enderror" rows="5"
            placeholder="Details about the product">{{ $digitalProduct -> content }}</textarea>
        <p class="text-muted">Leave blank, if digital content doesn't have automatic delivery. Otherwise each
            product should be put in separated lines.</p>
        @error('product_content', $errors)
        <div class="invalid-feedback d-block text-center">
            {{ $errors -> first('product_content') }}
        </div>
        @enderror
    </div>

    <div class="form-check mx-2 mb-2 ">
        <input class="form-check-input" type="checkbox" value="1" name="autodelivery" id="autodelivery"
            {{ $digitalProduct -> autodelivery == true ? 'checked' : '' }}>
        <label class="form-check-label" for="autodelivery">
            Automatic Delivery
        </label>
        <p class="text-muted">If checked, the product would be delivered from the number of lines in product content. Every line is a separate product.</p>
    </div>

    <div class="form-check mx-2 mb-2 ">
        <input class="form-check-input" type="checkbox" value="1" name="unlimited" id="unlimited"
            {{ $digitalProduct -> unlimited == true ? 'checked' : '' }}>
        <label class="form-check-label" for="unlimited">
            Unlimited Product
        </label>
        <p class="text-muted">If checked, your product will be unlimited and you can sell it as many times as you want. Please use only one line.</p>
    </div>

    <div class="form-row justify-content-center">
        <div class="form-group col-md-3 text-center">
            @if(request() -> is('profile/vendor/product/edit/*'))
            <button class="btn btn-outline-success" type="submit"><i class="far fa-save mr-2"></i> Save</button>
            <a href="{{ route('profile.vendor.product.edit', [$basicProduct, 'images']) }}"
                class="btn btn-outline-primary"><i class="fas fa-chevron-down mr-2"></i> Next</a>
            @elseif(request() -> is('admin/product/*'))
            <button class="btn btn-outline-success" type="submit"><i class="far fa-save mr-2"></i> Save</button>
            <a href="{{ route('admin.product.edit', [$basicProduct, 'images']) }}" class="btn btn-outline-primary"><i
                    class="fas fa-chevron-down mr-2"></i> Next</a>
            @else
            <button class="btn btn-outline-primary" type="submit"><i class="fas fa-chevron-down mr-2"></i> Next
            </button>
            @endif
        </div>
    </div>
</form>
@stop