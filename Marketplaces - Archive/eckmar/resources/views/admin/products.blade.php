@extends('master.admin')

@section('admin-content')
    <div class="row">
        <div class="col">
            <h4>
                List of Products
            </h4>
            <hr>
        </div>
    </div>


    <div class="row mt-2">

        <div class="col">
            @include('includes.flash.error')
            @include('includes.flash.success')
            @include('includes.flash.invalid')
            <form action="{{route('admin.products.query')}}" method="post" class="">
                {{csrf_field()}}
                <div class="row">


                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="">Product name: (optional)</label>
                            <input type="text" name="product" id="" class="form-control"
                                   placeholder="Display name's products"
                                   value="{{ request('product') ?? '' }}">
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="">User ID: (optional)</label>
                            <input type="text" name="user" id="" class="form-control"
                                   placeholder="Display user's products"
                                   value="@if(app('request')->input('user') !== null) {{app('request')->input('user')}}  @endif">
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <Label>Order: </Label><br>
                            <select name="order_by" id="" class="form-control">
                                <option value="newest"
                                        @if(app('request')->input('order_by') =='newest') selected @endif>Newest
                                </option>
                                <option value="oldest"
                                        @if(app('request')->input('order_by') =='oldest') selected @endif>Oldest
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="form-group" style="margin-top:2em">
                            <button type="submit" class="btn btn-primary">Apply query</button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="col">

        </div>
    </div>
    <table class="table">
        <thead>
        <tr>
            <th>Title</th>
            <th>Category</th>
            <th>Price (best)</th>
            <th>Type</th>
            <th>Vendor</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <form action="{{route('admin.product.delete')}}" method="post">
            {{csrf_field()}}

            @if($products->count() == 0 )
                <tr>
                    <td colspan="6" class="text-center">
                        <h4 class="mt-5">No products found</h4>
                    </td>
                </tr>
            @else
                @foreach($products as $product)
                    <tr>
                        <td>
                            <strong>{{$product->name}}</strong>
                        </td>
                        <td>
                            {{$product->category->name}}
                        </td>
                        <td>
                            @include('includes.currency', ['usdValue' => $product->price_from])
                        </td>
                        <td>
                            <span class="badge badge-info">{{$product->type}}</span>
                        </td>
                        <td>
                            <a href="{{route('admin.users.view',['user'=>$product->user->id])}}">{{$product->user->username}}</a>
                        </td>
                        <td>
                            @isModuleEnabled('FeaturedProducts')
                                @include('featuredproducts::markasfeatured')
                            @endisModuleEnabled

                            <a href="{{ route('admin.product.edit', $product -> id) }}" class="btn btn-outline-mblue">
                                <i class="fas fa-pen-square"></i>
                            </a>
                            <button type="submit" value="{{$product->id}}" name="product_id"
                                    class="btn btn-outline-danger">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                @endforeach

            @endif
        </form>

        </tbody>
    </table>

    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="text-center">
                {{$products->links()}}
            </div>
        </div>
    </div>


@stop