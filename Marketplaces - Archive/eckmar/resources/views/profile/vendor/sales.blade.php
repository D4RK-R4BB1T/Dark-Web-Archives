@extends('master.profile')

@section('title', 'Purchases')

@section('profile-content')
    @include('includes.flash.success')
    @include('includes.flash.error')

    <h1 class="mb-3">Sales</h1>

    <ul class="nav nav-tabs nav-fill mb-3">
        <li class="nav-item">
            <a class="nav-link @if(!array_key_exists($state, \App\Purchase::$states)) active @endif" href="{{ route('profile.sales') }}">
                All ({{ auth() -> user() -> vendor -> salesCount() }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if($state == 'purchased') active @endif" href="{{ route('profile.sales', 'purchased') }}">
                Purchased ({{ auth() -> user() -> vendor -> salesCount('purchased') }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if($state == 'sent') active @endif" href="{{ route('profile.sales', 'sent') }}">
                Sent ({{ auth() -> user() -> vendor -> salesCount('sent') }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if($state == 'delivered') active @endif" href="{{ route('profile.sales', 'delivered') }}">
                Delivered ({{ auth() -> user() -> vendor -> salesCount('delivered') }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if($state == 'disputed') active @endif" href="{{ route('profile.sales', 'disputed') }}">
                Disputed ({{ auth() -> user() -> vendor -> salesCount('disputed') }})
            </a>
        </li>
    </ul>

    <table class="table table-hover table-striped">
        <thead>
        <tr>
            <th>Product</th>
            <th>#</th>
            <th>Buyer</th>
            <th>Shipping</th>
            <th>Total</th>
            <th>Address</th>
            <th>ID</th>
        </tr>
        </thead>
        <tbody>
        @foreach($sales as $purchase)
            <tr>
                <td>
                    <a href="{{ route('product.show', $purchase -> offer -> product) }}">{{ $purchase -> offer -> product -> name }}</a>
                    @if($purchase -> isDisputed() && $purchase -> dispute -> isResolved())
                        <span class="badge badge-success">resolved</span>
                    @endif
                </td>
                <td class="text-right">
                    {{ $purchase -> quantity }}
                </td>
                <td class="text-right">
                    @if($purchase -> buyer)
                    {{ $purchase -> buyer -> username }}
                    @else
                        <span class="text-muted">User deleted account!</span>
                    @endif
                </td>
                <td class="text-right">
                    @if($purchase -> shipping)
                        <p class="text-muted text-sm-center">{{ $purchase -> shipping -> name }} - @include('includes.currency', ['usdValue' => $purchase -> shipping -> price])</p>
                    @else
                        <span class="badge badge-info">Digital delivery</span>
                    @endif
                </td>
                <td class="text-right">
                    <span class="badge badge-mblue">@include('includes.currency', ['usdValue' => $purchase -> value_sum])</span>
                </td>
                <td>
                    <input type="text" readonly="readonly" class="form-control" value="{{ $purchase -> address }}">
                </td>
                <td>
                    <a href="{{ route('profile.sales.single', $purchase) }}" class="btn btn-sm {{ $purchase -> isCanceled() ? 'btn-danger' : 'btn-primary' }}">
                        @if($purchase->isCanceled()) <em>Canceled</em> @else {{ $purchase -> short_id }} @endif
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{ $sales -> links('includes.paginate') }}
@stop