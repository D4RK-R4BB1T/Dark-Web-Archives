<div class="row">
    <div class="col mt-2">
        <div class="card mt-5">
            <div class="card-header">
                Detailed Search
            </div>
            <div class="card-body">
                <form action="{{route('search')}}" method="post">
                    {{csrf_field()}}
                    <div class="form-group">
                        <label for="search">Search terms:</label>
                        <input type="text" name="search" id="search" class="form-control" value="{{app('request')->input('query')}}">
                    </div>
                    <div class="form-group">
                        <label for="user">User:</label>
                        <input type="text" name="user" id="user" class="form-control" value="{{app('request')->input('user')}}">
                    </div>
                    <div class="form-group">
                        <label for="">Category:</label>
                        <select class="form-control" id="category" name="category">
                            <option selected value="any">Any</option>
                            @foreach($categories as $category)
                                <option value="{{$category->name}}" @if(app('request')->input('category') == $category->name) selected @endif>{{$category->name}}</option>
                                @if($category -> children -> isNotEmpty())
                                    @foreach($category->children as $child)
                                        <option value="{{$child->name}}" @if(app('request')->input('category') == $child->name) selected @endif> > {{$child->name}}</option>
                                        @if($child -> children -> isNotEmpty())
                                            @foreach($child->children as $subChild)
                                                <option value="{{$subChild->name}}" @if(app('request')->input('category') == $subChild->name) selected @endif> >> {{$subChild->name}}</option>
                                            @endforeach
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="product_type">Product type:</label>
                        <select class="form-control" id="product_type" name="product_type">
                            <option selected value="all">All</option>
                            <option value="digital" @if(app('request')->input('type') == 'digital') selected @endif>Digital</option>
                            <option value="physical" @if(app('request')->input('type') == 'physical') selected @endif>Physical</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="">Price range:</label>
                        <input type="number" name="minimum_price" id="" placeholder="Minimum price USD"
                               class="form-control" value="{{app('request')->input('price_min')}}" step="0.01">
                        <input type="number" name="maximum_price" id="" placeholder="Maximum price USD"
                               class="form-control mt-2" value="{{app('request')->input('price_max')}}" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="">Order By:</label>
                        <select class="form-control" id="order_by" name="order_by">
                            <option @if(app('request')->input('order_by') == 'price_asc' ||app('request')->input('order_by') == null) selected @endif value="price_asc">Price: Low to High</option>
                            <option @if(app('request')->input('order_by') == 'price_desc') selected @endif value="price_desc">Price: High to Low</option>
                            <option @if(app('request')->input('order_by') == 'newest') selected @endif value="newest">Newest</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
