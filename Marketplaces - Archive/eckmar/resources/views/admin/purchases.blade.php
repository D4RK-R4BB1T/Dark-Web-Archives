@extends('master.admin')

@section('admin-content')
    <div class="row">
        <div class="col">
            <h4>
                List of all Purchases
            </h4>
            <hr>
        </div>
    </div>


    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Buyer</th>
            <th>Vendor</th>
            <th>Total</th>
            <th>Time</th>
        </tr>
        </thead>
        <tbody>

            @if($purchases->count() == 0 )
                <tr>
                    <td colspan="6" class="text-center">
                        <h4 class="mt-5">No products found</h4>
                    </td>
                </tr>
            @else
                @foreach($purchases as $purchase)
                    <tr>
                        <td>
                            <a href="{{ route('admin.purchase', $purchase) }}" class="btn btn-sm btn-mblue mt-1">{{ $purchase -> short_id }}</a>
                        </td>
                        <td>
                            <a href="{{ route('product.show', $purchase -> offer -> product) }}">{{ $purchase -> offer -> product -> name }}</a>
                        </td>
                        <td>
                            {{ $purchase->quantity }}
                        </td>
                        <td>
                            <span class="badge badge-info">{{ $purchase->buyer->username }}</span>
                        </td>
                        <td>
                            <a href="{{route('admin.users.view',['user'=>$purchase->vendor->user->id])}}" class="badge badge-primary">{{$purchase->vendor->user->username}}</a>
                        </td>
                        <td>
                            @include('includes.currency', ['usdValue' => $purchase -> value_sum])
                        </td>
                        <td>
                            {{ $purchase -> created_at -> diffForHumans() }}
                        </td>
                    </tr>
                @endforeach

            @endif
        </tbody>
    </table>

    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="text-center">
                {{$purchases->links()}}
            </div>
        </div>
    </div>


@stop