@extends('master.product')

@section('product-content')
    <p>Ships {{ $product -> specificProduct() -> shipsTo() }}: <em>{{ $product -> specificProduct() -> countriesLong() }}</em></p>
    <p>Ships from <strong>{{ $product -> specificProduct() -> shipsFrom() }}</p></strong>
    <table class="table table-hover table-striped">
        <thead>
        <tr>
            <th scope="col">Delivery name</th>
            <th scope="col">Duration</th>
            <th scope="col">Price</th>
            <th>Quantity</th>
        </tr>
        </thead>
        <tbody>
            @foreach($product -> specificProduct() -> shippings as $shipping)
            <tr>
                <td>{{ $shipping -> name }}</td>
                <td>{{ $shipping -> duration }}</td>
                <td>@include('includes.currency', ['usdValue' => $shipping -> dollars ])</td>
                <td>{{ $shipping -> from_quantity }} to {{ $shipping -> to_quantity }} {{ str_plural($product -> mesure) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

@stop