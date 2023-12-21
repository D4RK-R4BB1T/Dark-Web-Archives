@extends('master.product')

@section('product-content')


    <div class="row">
        <div class="col-md-4">
            <p class="text-muted m-0">Posted by:</p>
            <h4><a href="{{ route('vendor.show', $product -> user) }}">{{ $product -> user -> username }}</a></h4>

            <p class="text-muted">
                Number of sales: <span
                        class="badge badge-info">{{ $product -> user -> vendor -> salesCount('delivered') }}</span>
                <br>
                Number of received feedback: <span class="badge badge-info">{{ $product -> user -> vendor -> countFeedback() }}</span>
            </p>

            <table class="table table-striped">
                <tr>
                    <th colspan="3" class="text-center">
                        Average feedback rates
                    </th>
                </tr>
                <tr>
                    <th>Quality:</th>
                    <td>{{ $product -> user -> vendor -> avgRate('quality_rate') }}</td>
                    <td>
                        @include('includes.purchases.stars', ['stars' => $product -> user -> vendor -> roundAvgRate('quality_rate')])
                    </td>
                </tr>
                <tr>
                    <th>Communication:</th>
                    <td>{{ $product -> user -> vendor -> avgRate('communication_rate') }}</td>
                    <td>
                        @include('includes.purchases.stars', ['stars' => $product -> user -> vendor -> roundAvgRate('communication_rate')])
                    </td>
                </tr>
                <tr>
                    <th>Shipping:</th>
                    <td>{{ $product -> user -> vendor -> avgRate('shipping_rate') }}</td>
                    <td>@include('includes.purchases.stars', ['stars' => $product -> user -> vendor -> roundAvgRate('shipping_rate')])</td>
                </tr>

            </table>

        </div>


    </div>

@stop