@extends('master.product')

@section('product-content')

    @if($product -> hasFeedback())
        <h3 class="mb-3">Feedback ({{ count($product -> feedback) }})</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Quality rate ({{ $product -> avgRate('quality_rate') }})</th>
                    <th>Communication rate ({{ $product -> avgRate('communication_rate') }})</th>
                    <th>Shipping rate ({{ $product -> avgRate('shipping_rate') }})</th>
                    <th>Comment</th>
                </tr>
            </thead>
            <tbody>
                @foreach($product -> feedback as $feedback)
                    <tr>
                        <td>
                            @include('includes.purchases.stars', ['stars' => $feedback -> quality_rate])
                        </td>
                        <td>
                            @include('includes.purchases.stars', ['stars' => $feedback -> communication_rate])
                        </td>
                        <td>
                            @include('includes.purchases.stars', ['stars' => $feedback -> shipping_rate])
                        </td>
                        <td>
                            {{ $feedback -> comment }}
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>
    @else
        <div class="alert alert-warning">There is no available feedback for this product, yet.</div>
    @endif

@stop