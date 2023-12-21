@extends('master.main')

@section('content')
    <div class="row">
        <div class="col-md-12">
            @include('includes.flash.error')
            <h2 class="mb-3">Checkout ({{ $numberOfItems }})</h2>

            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>#</th>
                        <th>Price</th>
                        <th>Paying with</th>
                        <th>Payment type & Shipping</th>
                        <th>Total</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $productId => $item)
                        <tr>
                            <td>
                                <a href="{{ route('product.show', $productId) }}">{{ $item -> offer -> product -> name }}</a>
                            </td>
                            <td class="text-center">
                                {{ $item -> quantity }}
                            </td>
                            <td class="text-center">
                                <span class="badge badge-mblue">
                                    @include('includes.currency', ['usdValue' => $item -> offer -> price])
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info">{{ strtoupper(\App\Purchase::coinDisplayName($item -> coin_name)) }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-primary">{{ \App\Purchase::$types[$item->type] }}</span>

                                @if($item -> shipping)
                                    {{ $item -> shipping -> name }} -
                                    @include('includes.currency', ['usdValue' => $item -> shipping -> price])
                                @else
                                    <span class="badge badge-info">Digital delivery</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-mblue">
                                    @include('includes.currency', ['usdValue' => $item -> value_sum])
                                </span>
                            </td>
                            <td>
                                @if($item -> message)
                                    @if(\App\Message::messageEncrypted($item -> message))
                                        <textarea class="form-control"  readonly rows="5">{{ $item -> message }}</textarea>
                                    @else
                                        <p class="text-muted">
                                            {{ $item -> message }}
                                        </p>

                                    @endif
                                @else
                                    <span class="badge badge-info">No message</span>
                                @endif

                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
        <div class="col-md-4">
            <a href="{{ route('profile.cart') }}" class="btn btn-lg btn-danger">
                <i class="fas fa-chevron-left mr-2"></i>
                Back to cart
            </a>
        </div>
        <div class="col-md-8 text-right">
            <h3 class="text-right d-inline-block mr-2">Total: @include('includes.currency', ['usdValue' => $totalSum])</h3>
        </div>
        {{--<div class="col-md-6 mt-3 justify-content-center text-center">--}}
            {{--<form action="{{ route('profile.cart.make.purchases') }}">--}}
                {{--<input type="hidden" name="cointype" value="xmr">--}}
                {{--<button type="submit"  class="btn btn-primary btn-lg">--}}
                    {{--<i class="fab fa-monero mr-2"></i>--}}
                    {{--Pay with Monero Escrow--}}
                {{--</button>--}}
            {{--</form>--}}
        {{--</div>--}}
        <div class="col-md-12 mt-3 justify-content-end text-right">
            <form action="{{ route('profile.cart.make.purchases') }}">
                {{--<input type="hidden" name="cointype" value="btc">--}}
                <button type="submit"  class="btn btn-mblue btn-lg">
                    <i class="fas fa-shopping-cart mr-2"></i>
                    Purchase
                </button>
            </form>
        </div>


    </div>

@stop