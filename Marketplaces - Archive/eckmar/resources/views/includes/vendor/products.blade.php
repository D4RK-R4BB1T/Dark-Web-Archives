<div class="row">
    <div class="col-10">
        <h4>Items for sale <a href="{{route('search',['user'=>$vendor->username])}}" class="ml-2" style="font-size: 0.9em; text-decoration: none;">({{$vendor->products()->count()}})</a></h4>
    </div>
    <div class="col-2 text-md-right">
        <a href="{{route('search',['user'=>$vendor->username])}}">See all items</a>
    </div>
</div>

<div class="row mt-2">
    @foreach($vendor->recentProducts(4) as $product)
        <div class="col-md-3 col-sm-6 mb-2">
            @include('includes.product.card',$product)
        </div>
    @endforeach
</div>