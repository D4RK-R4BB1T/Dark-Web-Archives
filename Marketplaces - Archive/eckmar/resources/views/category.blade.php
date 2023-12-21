@extends('master.main')

@section('title', $category -> name . ' category')

@section('content')
    <div class="row">
        <div class="col-md-3">
            @include('includes.categories')
        </div>
        <div class="col-md-9">
            <div class="row">
                <h1 class="col-md-11">{{ $category -> name}}
                    <small>- category</small>
                </h1>
                <div class="col-md-1 text-lg-right">
                    @include('includes.viewpicker')
                </div>
            </div>
            <hr>

            @if($productsView == 'list')
                @foreach($products as $product)
                    @include('includes.product.row', ['product' => $product])
                @endforeach
            @else
                @foreach($products->chunk(3) as $chunks)
                    <div class="row mt-3">
                        @foreach($chunks as $product)
                            <div class="col-md-4 my-md-0 my-2 col-12">
                                @include('includes.product.card', ['product' => $product])
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endif

            {{ $products -> links('includes.paginate') }}
        </div>

    </div>

@stop
