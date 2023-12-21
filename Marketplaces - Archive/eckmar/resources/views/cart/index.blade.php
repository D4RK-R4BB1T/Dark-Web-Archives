@extends('master.main')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <h2 class="mb-3">Cart ({{ $numberOfItems }})</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('profile.cart.clear') }}" class="btn btn-lg btn-danger">
                <i class="fas fa-trash-alt mr-2"></i>
                Clear
            </a>
        </div>
        <div class="col-md-12">
            @include('includes.flash.error')
            @include('includes.flash.success')
            <div class="form-row bg-dark text-white text-center rounded py-2">
                <div class="col-md-2 justify-content-center">
                    Product name
                </div>
                <div class="col-md-1 justify-content-center">
                    {{ \App\Marketplace\Utility\CurrencyConverter::getSymbol(\App\Marketplace\Utility\CurrencyConverter::getLocalCurrency()) }} per item
                </div>
                <div class="col-md-1">
                    Coin
                </div>
                <div class="col-md-1 justify-content-center">
                    Amount
                </div>
                <div class="col-md-2 justify-content-center">
                    Delivery/Payment
                </div>

                <div class="col-md-3 justify-content-center">
                    Message
                </div>
            </div>

        </div>
        @if(!empty($items))
            @foreach($items as $productId => $item)
        <div class="col-md-12  my-1 py-2">
            <form action="{{ route('profile.cart.add', \App\Product::find($productId)) }}" method="POST">
                {{ csrf_field() }}
                <div class="form-row bg-light">
                    <div class="col-md-2">
                        <a href="{{ route('product.show', $item -> offer -> product) }}">
                            <h4>{{ $item -> offer -> product -> name }}</h4>
                        </a>
                        by
                        <a class="badge badge-info" href="{{ route('vendor.show', $item -> offer -> product -> user) }}">
                            {{ $item -> vendor -> user -> username }}
                        </a>
                    </div>
                    <div class="col-md-1 d-flex align-items-center">
                        <h5 class="text-center w-100">
                            <span class="badge badge-info">
                                @include('includes.currency', ['usdValue' => $item -> offer -> price])
                            </span>
                        </h5>
                    </div>
                    <div class="col-md-1  d-flex align-items-center justify-content-center">
                        @if(count($item -> offer -> product -> getCoins()) > 1)
                        <select name="coin" id="coin" class="form-control form-control-sm">
                            @foreach($item -> offer -> product -> getCoins() as $coin)
                                <option value="{{ $coin }}" {{ $coin == $item -> coin_name ? 'selected' : ''}} >{{ strtoupper(\App\Purchase::coinDisplayName($coin)) }}</option>
                            @endforeach
                        </select>
                        @elseif(count($item -> offer -> product -> getCoins()) == 1)
                            <input type="hidden" name="coin" value="{{ $item -> offer -> product -> getCoins()[0] }}">
                            <input type="text" value="{{ strtoupper(\App\Purchase::coinDisplayName($item -> offer -> product -> getCoins()[0])) }}" class="form-control form-control-sm disabled" disabled>
                        @endif


                    </div>
                    <div class="col-md-1 d-flex align-items-center">
                        <input type="number" class="form-control form-control-sm" name="amount" id="amount" min="1" max="{{ $item -> offer -> product -> quantity }}" placeholder="Quantity" value="{{ $item -> quantity }}"/>
                    </div>
                    <div class="col-md-2 text-center">
                        @if($item -> offer -> product -> isPhysical())
                        <select name="delivery" id="delivery" class="form-control form-control-sm">
                            @foreach($item -> offer -> product -> specificProduct() -> shippings as $shipping)
                                <option value="{{ $shipping -> id }}" @if($shipping -> id == $item -> shipping -> id) selected @endif>{{ $shipping -> long_name }}</option>
                            @endforeach
                        </select>
                        @else
                        <span class="badge badge-info">Digital delivery</span>
                        @endif
                            <br>
                        @if(count($item -> offer -> product -> getTypes()) > 1)
                        <select name="type" id="type" class="form-control form-control-sm">
                            @foreach($item -> offer -> product -> getTypes() as $type)
                                <option value="{{ $type }}" {{ $type == $item -> type ? 'selected' : ''}} >{{ \App\Purchase::$types[$type] }}</option>
                            @endforeach
                        </select>
                        @elseif(count($item -> offer -> product -> getTypes()) == 1)
                            <input type="hidden" name="type" value="{{ $item -> offer -> product -> getTypes()[0] }}">
                            <input type="text" value="{{ \App\Purchase::$types[$item -> offer -> product -> getTypes()[0]]  }}" class="form-control form-control-sm disabled" disabled>
                        @endif
                    </div>

                    <div class="col-md-3 d-flex align-items-stretch">
                        <textarea name="message" id="message" rows="3" placeholder="Message will be encrypted with vendor's PGP key. Click on edit to save message!" style="resize: 0" class="form-control form-control-sm">{{ $item -> message }}</textarea><br>
                    </div>
                    <div class="col-md-2 d-flex align-items-center justify-content-around">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="far fa-edit mr-2"></i>
                            Edit
                        </button>
                        <a href="{{ route('profile.cart.remove', $productId) }}" class="btn btn-outline-danger">
                            <i class="fas fa-minus-circle"></i>
                        </a>
                    </div>
                </div>

            </form>
        </div>
    @endforeach
        @else
            <div class="col-md-12 my-3">
                <div class="alert alert-warning">There are no items in cart!</div>
            </div>
        @endif

        <div class="col-md-12 py-2 justify-content-end">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="m-0">Total sum: @include('includes.currency', ['usdValue' => $totalSum])</h4>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('profile.cart.checkout') }}" class="btn ml-auto btn-lg btn-mblue">
                        <i class="fas fa-cart-arrow-down mr-2"></i>
                        Checkout
                    </a>
                </div>
            </div>

        </div>
    </div>

@stop