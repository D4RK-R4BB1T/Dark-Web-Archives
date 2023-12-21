@extends('includes.profile.addingform')

@section('form-content')
@if(optional($productsOffers) -> isNotEmpty())
    <table class="table table-striped table-hover ">
    <thead>
        <tr>
        <th scope="col">Price ({{ \App\Marketplace\Utility\CurrencyConverter::getLocalSymbol() }})</th>
        <th scope="col">Minimum quantity</th>
        <th scope="col"></th>
    </tr>
    </thead>
    <tbody>
        @foreach($productsOffers as $offer)
            <tr>
                <th>{{ $offer -> local_price }}</th>
                <td>{{ $offer -> min_quantity }}</td>
                <td class="text-right">
                    <a href="{{ route('profile.vendor.product.offers.remove', [ $offer -> min_quantity, $basicProduct]) }}" class="btn btn-sm btn-outline-danger">Remove</a>
                </td>
            </tr>
        @endforeach
    </tbody>
    </table>
@else
    <div class="alert alert-warning">You don't have any offer please add at least one!</div>
@endif

<h3 class="mt-3">Add offer</h3>
<hr>
<form method="POST" action="{{ route('profile.vendor.product.offers.add', $basicProduct) }}">
    {{ csrf_field() }}
    <div class="form-row">
        <div class="col">
            <input type="number" step=".01" min="0.01" class="form-control @error('price', $errors) is-invalid @enderror" name="price" value="{{ old('min_quantity') }}" placeholder="Price in {{ \App\Marketplace\Utility\CurrencyConverter::getLocalSymbol() }}">
            @error('price', $errors)
            <div class="invalid-feedback">
                {{ $errors -> first('price') }}
            </div>
            @enderror
        </div>
        <div class="col">
            <input type="min_quantity" step="1" min="1" class="form-control @error('min_quantity', $errors) is-invalid @enderror" value="{{ old('min_quantity') }}" name="min_quantity" placeholder="Minimum quantity for this price">
            @error('price', $errors)
            <div class="invalid-feedback">
                {{ $errors -> first('price') }}
            </div>
            @enderror
        </div>
        <div class="col">
            <button class="btn btn-outline-success" type="submit"><i class="fas fa-plus mr-2"></i> Add offer</button>
        </div>
    </div>
</form>

<div class="col-md-12 text-center mt-3">
    @if(request() -> is('profile/vendor/product/edit/*'))
        <a href="{{ route('profile.vendor.product.edit', [$basicProduct, $basicProduct -> afterOffers()]) }}" class="btn btn-outline-primary"><i class="fas fa-chevron-down mr-2"></i>  Next</a>
    @elseif(request() -> is('admin/product/*'))
        <a href="{{ route('admin.product.edit', [$basicProduct, $basicProduct -> afterOffers()]) }}" class="btn btn-outline-primary"><i class="fas fa-chevron-down mr-2"></i>  Next</a>
    @else
        <a href="{{ route('profile.vendor.product.' . ( session('product_type') == 'physical' ? 'delivery' : 'digital' ) ) }}" class="btn btn-outline-primary"><i class="fas fa-chevron-down mr-2"></i>  Next</a>
    @endif
</div>
@stop