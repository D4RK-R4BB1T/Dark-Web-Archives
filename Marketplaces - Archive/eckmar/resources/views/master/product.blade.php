@extends('master.main')

@section('title','Product - ' . $product -> name )

@section('content')


    <nav class="main-breadcrumb" aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('home') }}">Products</a>
            </li>
            @foreach($product -> category -> parents() as $ancestor)
                <li class="breadcrumb-item" aria-current="page"><a
                            href="{{ route('category.show', $ancestor) }}">{{ $ancestor -> name }}</a></li>
            @endforeach
            <li class="breadcrumb-item active" aria-current="page"><a
                        href="{{ route('category.show', $product -> category) }}">{{ $product -> category -> name }}</a>
            </li>
        </ol>
    </nav>


    <div class="row">
        <div class="col-md-4">
            <div class="slider">
                <div class="slides">
                    @php $i = 1; @endphp
                    @foreach($product -> images() -> orderBy('first', 'desc') -> get() as $image)
                        <div id="slide-{{ $i++ }}">
                            <img src="{{ asset('storage/' . $image -> image) }}">
                        </div>
                    @endforeach
                </div>

                @php $i = 1; @endphp
                @foreach($product -> images as $image)
                    <a href="#slide-{{ $i }}">{{ $i++ }}</a>
                @endforeach
            </div>
        </div>

        <div class="col-md-5">
            @include('includes.flash.error')

            <h2>{{ $product -> name }}</h2>
            <hr>

            <div class="row">
                <div class="col-md-12 text-center">

                    <form action="{{ route('profile.cart.add', $product) }}"  method="POST">
                        {{ csrf_field() }}

                    <table class="table border-0 text-left table-borderless">
                        <tbody>

                        <tr>
                            <td class="text-right text-muted">Quality rate:</td>
                            <td>
                                @include('includes.purchases.stars', ['stars' => (int)$product->avgRate('quality_rate')])
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right text-muted">
                                Type
                            </td>
                            <td>
                                <strong class="badge badge-info">{{ ucfirst($product -> type) }}</strong>
                            </td>
                        </tr>
                        @if(!$product -> isUnlimited())
                        <tr>
                            <td class="text-right text-muted">
                                Offers
                            </td>
                            <td>
                                <ul>
                                    @foreach($product -> offers as $offer)
                                        <li>
                                            <strong>@include('includes.currency', ['usdValue' => $offer -> dollars])</strong> per {{ str_plural($product -> mesure, 1) }},
                                            for at least {{ $offer -> min_quantity }} {{ str_plural('product', $offer -> min_quantity) }}
                                        </li>
                                    @endforeach
                                </ul>

                            </td>
                        </tr>
                        @else
                        <tr>
                            <td class="text-right text-muted">
                                Price
                            </td>
                            <td>
                                @foreach($product -> offers as $offer)
                                    <strong>@include('includes.currency', ['usdValue' => $offer -> dollars])</strong>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-right text-muted">
                                Coins
                            </td>
                            <td>
                                @foreach($product -> getCoins() as $coin)
                                    <span class="badge badge-indigo">{{ strtoupper(\App\Purchase::coinDisplayName($coin)) }}</span>
                                @endforeach
                            </td>
                        </tr>
                        <tr>
                            @if(!$product -> isUnlimited())
                            <td class="text-right text-muted">Left/Sold</td>
                            <td>
                                <span class="badge badge-light">{{ $product -> quantity }} {{ str_plural($product -> mesure, $product -> quantity) }}</span>/
                                <span class="badge badge-light">{{ $product -> orders }} {{ str_plural($product -> mesure, $product -> orders) }} </span>
                            </td>
                            @endif
                        </tr>
                        <tr>
                            <td colspan="2">
                                @if($product->user->vendor->experience < 0)
                                    <p class="text-danger border border-danger rounded p-1 mt-2"><span
                                                class="fas fa-exclamation-circle"></span> Negative experience, trade with caution!
                                    </p>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            @if($product -> isPhysical())
                                <td class="text-muted text-right">
                                    <label for="delivery">Delivery method:</label>
                                </td>
                                <td>
                                    <select name="delivery" id="delivery"
                                            class="form-control form-control-sm @if($errors -> has('delivery')) is-invalid @endif">
                                        @foreach($product -> specificProduct() -> shippings as $shipping)
                                            <option value="{{ $shipping -> id }}">{{ $shipping -> long_name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            @endif
                        </tr>

                        <tr class="bg-light">
                            <td class="text-right text-muted">
                                <label for="coin">Pay with Coin:</label>
                            </td>
                            <td>
                                @if(count($product -> getCoins()) > 1)
                                    <select name="coin" id="coin" class="form-control form-control-sm">
                                        @foreach($product -> getCoins() as $coin)
                                            <option value="{{ $coin }}">{{ strtoupper(\App\Purchase::coinDisplayName($coin)) }}</option>
                                        @endforeach
                                    </select>
                                @elseif(count($product -> getCoins()) == 1)
                                    <span class="badge badge-mblue">{{ strtoupper(\App\Purchase::coinDisplayName($product -> getCoins()[0])) }}</span>
                                    <input type="hidden" name="coin" value="{{ $product -> getCoins()[0] }}">
                                @endif
                            </td>
                        </tr>
                        <tr class="bg-light">
                            <td class="text-right text-muted">
                                <label for="type">Purchase type:</label>
                            </td>
                            <td>
                                @if(count($product -> getTypes()) > 1)
                                    <select name="type" id="type" class="form-control form-control-sm">
                                        @foreach($product -> getTypes() as $type)
                                            <option value="{{ $type }}">{{ \App\Purchase::$types[$type] }}</option>
                                        @endforeach
                                    </select>
                                @elseif(count($product -> getTypes()) == 1)
                                    <span class="badge badge-mblue">{{ \App\Purchase::$types[$product -> getTypes()[0]] }}</span>
                                    <input type="hidden" name="type" value="{{ $product -> getTypes()[0] }}">
                                @endif
                            </td>
                        </tr>
                        <tr class="bg-light">

                            <td class="text-right text-muted">
                            @if(!$product -> isUnlimited())
                                <label for="amount">Amount:</label>
                            @endif
                            </td>
                            <td class="row">
                                @if($product -> isUnlimited())
                                <input style="display: none;" type="number" min="1" name="amount" id="amount"
                                        value="1"
                                        max="{{ $product -> quantity }}"
                                        class="@if($errors -> has('amount')) is-invalid @endif form-control form-control-sm"
                                        placeholder="Amount of {{ str_plural($product -> mesure) }}"/>
                                @else
                                <div class="col-md-5">
                                    <input type="number" min="1" name="amount" id="amount"
                                           value="1"
                                           max="{{ $product -> quantity }}"
                                           class="@if($errors -> has('amount')) is-invalid @endif form-control form-control-sm"
                                           placeholder="Amount of {{ str_plural($product -> mesure) }}"/>
                                />
                                @endif
                                <div class="col-md-7">
                                    <button class="btn btn-sm btn-block mb-2 btn-primary"><i class="fas fa-plus mr-2"></i>Add to
                                        cart
                                    </button>
                                    @auth
                                        @if(auth() -> user() -> isWhishing($product))
                                            <a href="{{ route('profile.wishlist.add', $product) }}"
                                               class="btn btn-outline-secondary btn-block btn-sm"><i class="far fa-heart"></i> Remove
                                                from
                                                wishlist</a>
                                        @else
                                            <a href="{{ route('profile.wishlist.add', $product) }}"
                                               class="btn btn-sm btn-block btn-outline-danger"><i
                                                        class="fas fa-heart"></i> Add to wishlist</a>
                                        @endif
                                    @endauth
                                </div>
                            </td>
                        </tr>

                        </tbody>
                    </table>

                    </form>
                        @include('includes.flash.invalid')

                </div>


            </div>
        </div>

        {{-- Shop with Confidence --}}
        <div class="col-md-3">
            <div class="card mb-2">
                <div class="card-body">
                    <h6>
                        <i class="fas fa-shield-alt"></i>
                        Shop with Confidence
                    </h6>
                    <div class="text-muted">
                        You are Escrow protected for each product in the market!
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    Seller information
                </div>
                <div class="card-body">
                    <div class="btn-group">
                        <a class="btn btn-light btn-sm" href="{{ route('vendor.show', $product -> user) }}">
                            <span >{{ $product -> user -> username }}</span></a>

                        <span class="btn btn-primary active btn-sm">Level {{$product->user->vendor->getLevel()}}</span>
                    </div>

                    @php
                    $vendor = $product->user;
                    @endphp
                    <div class="row my-1 text-md-center">
                        <div class="col-4">
                            <span class="fas fa-plus-circle text-success"></span> {{$vendor->vendor->countFeedbackByType('positive')}}
                        </div>
                        <div class="col-4">
                            <span class="fas fa-stop-circle text-secondary"></span> {{$vendor->vendor->countFeedbackByType('neutral')}}

                        </div>
                        <div class="col-4">
                            <span class="fas fa-minus-circle text-danger"></span> {{$vendor->vendor->countFeedbackByType('negative')}}
                        </div>
                    </div>
                    <hr>
                        <a href="{{ route('profile.messages').'?otherParty='.$product -> user ->username}}" class="btn mb-1 btn-outline-secondary"><span class="fas fa-envelope"></span> Send message</a>
                        <a href="{{route('search',['user'=>$product->user->username])}}"  class="btn mb-1 btn-outline-info">Seller's products ({{$product -> user ->products()->count()}})</a>

                </div>
            </div>
        </div>

    </div>

    {{-- Product menu --}}
    <ul id="productsmenu" class="my-4 nav nav-tabs nav-fill">
        <li class="nav-item">
            <a class="nav-link @isroute('product.show') active @endisroute"
               href="{{ route('product.show', $product) }}#productsmenu">Details</a>
        </li>
        <li class="nav-item">
            <a class="nav-link @isroute('product.feedback') active @endisroute"
               href="{{ route('product.feedback', $product) }}#productsmenu">Feedback</a>
        </li>
        @if($product -> isPhysical())
            <li class="nav-item">
                <a class="nav-link @isroute('product.delivery') active @endisroute"
                   href="{{ route('product.delivery', $product) }}#productsmenu">Delivery</a>
            </li>
        @endif


    </ul>

    @yield('product-content')
@stop
