@extends('includes.profile.addingform')

@section('form-content')
    @if(!empty($productsShipping))
    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <th scope="col">Name</th>
            <th scope="col">Price({{ \App\Marketplace\Utility\CurrencyConverter::getLocalSymbol() }})</th>
            <th scope="col">Duration</th>
            <th scope="col">Minimum quantity</th>
            <th scope="col">Maximum quantity</th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
        @php
            $i = 0;
        @endphp
        @foreach($productsShipping as $shipping)
            <tr>
                <th>{{ $shipping -> name }}</th>
                <th>{{ $shipping -> local_value }}</th>
                <td>{{ $shipping -> duration }}</td>
                <td>{{ $shipping -> from_quantity }}</td>
                <td>{{ $shipping -> to_quantity }}</td>
                <td class="text-right">
                    @if($shipping -> exists)
                        <a href="{{ route('profile.vendor.product.delivery.remove', [$shipping -> id, $physicalProduct] ) }}" class="btn btn-sm btn-outline-danger">Remove</a>
                    @else
                        <a href="{{ route('profile.vendor.product.delivery.remove', $i) }}" class="btn btn-sm btn-outline-danger">Remove</a>
                    @endif
                </td>
            </tr>
            @php
                $i++;
            @endphp
        @endforeach
        </tbody>
    </table>
@else
    <div class="alert alert-warning">You don't have any offer please add at least one!</div>
@endif

<h3 class="mt-3">Add delivery option</h3>
<hr>
<form method="POST" action="{{ route('profile.vendor.product.delivery.new', $physicalProduct -> product) }}">
    {{ csrf_field() }}
    <div class="form-row">
        <div class="col-md-4 my-2">
            <input type="text" maxlength="10" class="form-control @error('name', $errors) is-invalid @enderror" value="{{ old('name') }}" name="name" placeholder="Name">
            @error('name', $errors)
            <div class="invalid-feedback">
                {{ $errors -> first('name') }}
            </div>
            @enderror
        </div>
        <div class="col-md-4 my-2">
            <input type="number" step=".01" min="0.01" class="form-control @error('price', $errors) is-invalid @enderror" value="{{ old('price') }}" name="price" placeholder="Price in {{ \App\Marketplace\Utility\CurrencyConverter::getLocalSymbol() }}(Ex. 15.99)">
            @error('price', $errors)
            <div class="invalid-feedback">
                {{ $errors -> first('price') }}
            </div>
            @enderror
        </div>
        <div class="col-md-4 my-2">
            <input type="text" maxlength="30" class="form-control @error('duration', $errors) is-invalid @enderror" name="duration" value="{{ old('duration') }}" placeholder="Duration(Ex. 1-2 weeks)">
            @error('duration', $errors)
            <div class="invalid-feedback">
                {{ $errors -> first('duration') }}
            </div>
            @enderror
        </div>
        <div class="col-md-4 my-2">
            <input type="number" step="1" min="1" class="form-control @error('from_quantity', $errors) is-invalid @enderror" name="from_quantity" value="{{ old('from_quantity') }}" placeholder="Minimum quantity for this delivery">
            @error('from_quantity', $errors)
            <div class="invalid-feedback">
                {{ $errors -> first('from_quantity') }}
            </div>
            @enderror
        </div>
       <div class="col-md-4 my-2">
            <input type="number" step="1" min="1" class="form-control @error('to_quantity', $errors) is-invalid @enderror" name="to_quantity" value="{{ old('to_quantity') }}" placeholder="Maximum quantity for this delivery">
            @error('to_quantity', $errors)
            <div class="invalid-feedback">
                {{ $errors -> first('to_quantity') }}
            </div>
            @enderror
        </div>
        <div class="col-md-4 my-2 text-right">
            <button class="btn btn-outline-success" type="submit"><i class="fas fa-plus mr-2"></i> Add shipping</button>
        </div>
    </div>
</form>

<h3 class="mt-3">Shipping countries</h3>
<hr>
<form method="POST" action="{{ route('profile.vendor.product.delivery.options', $physicalProduct) }}">
    {{ csrf_field() }}
    <div class="form-row">
        <div class="col-md-4 my-2">
            <label for="countries_option">Ships to</label>
            <select class="form-control" name="countries_option" id="countries_option">
                @foreach(\App\PhysicalProduct::$countriesOptions as $short => $optionName)
                    <option value="{{ $short }}" @if($short == $physicalProduct -> countries_option) selected @endif>{{ $optionName }}</option>
                @endforeach
            </select>


            @error('countries_option', $errors)
            <div class="invalid-feedback">
                {{ $errors -> first('name') }}
            </div>
            @enderror

        </div>
        <div class="col-md-4 my-2">
            <label for="countries">Included/excluded countries:</label>
            <select class="form-control " name="countries[]" multiple>
                @foreach(config('countries') as $countryShort => $countryName)
                    <option value="{{ $countryShort }}" @if(in_array($countryShort, $physicalProduct-> countriesArray())) selected @endif>{{ $countryName }}</option>
                @endforeach
            </select>
            @error('countries', $errors)
            <div class="invalid-feedback">
                {{ $errors -> first('countries') }}
            </div>
            @enderror
        </div>
        <div class="col-md-4 my-2">
            <label for="country_from">Country from:</label>
            <select class="form-control " name="country_from">
                @foreach(config('countries') as $countryShort => $countryName)
                    <option value="{{ $countryShort }}" @if($countryShort == $physicalProduct-> country_from) selected @endif>{{ $countryName }}</option>
                @endforeach
            </select>

            @error('country_from', $errors)
            <div class="invalid-feedback">
                {{ $errors -> first('country_from') }}
            </div>
            @enderror
        </div>


    </div>
    <div class="form-row justify-content-center">
        <div class="col-md-3 text-center">

            @if(request() -> is('profile/vendor/product/edit/*'))
                <button class="btn btn-outline-success" type="submit"><i class="far fa-save mr-2"></i> Save</button>
                <a href="{{ route('profile.vendor.product.edit', [$basicProduct, 'images']) }}" class="btn btn-outline-primary"><i class="fas fa-chevron-down mr-2"></i> Next</a>
            @elseif(request() -> is('admin/product/*'))
                <button class="btn btn-outline-success" type="submit"><i class="far fa-save mr-2"></i> Save</button>
                <a href="{{ route('admin.product.edit', [$basicProduct, 'images']) }}" class="btn btn-outline-primary"><i class="fas fa-chevron-down mr-2"></i> Next</a>
            @else
                <button class="btn btn-outline-primary" type="submit"><i class="fas fa-chevron-down mr-2"></i>  Next</button>
            @endif
        </div>
    </div>
</form>

@stop