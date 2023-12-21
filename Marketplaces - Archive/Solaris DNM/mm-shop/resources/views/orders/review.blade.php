{{--
This file is part of MM2-dev project.
Description: Order view page
--}}
@extends('layouts.master')

@section('title', 'Отзыв о товаре')

@section('content')
    @include('layouts.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
        'breadcrumbs' =>
        [
            BREADCRUMB_ORDERS,
            ['title' => 'Просмотр заказа', 'url' => url('/orders/' . $order->id)],
            ['title' => 'Отзыв о товаре']
        ]
    ])

    <div class="row">
        <div class="col-sm-6 col-md-6 col-lg-5">
            @include('orders.components.block-good', ['include_referrer_fee' => true])
            @include('layouts.components.block-shop', ['shop' => $order->shop])
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-13 col-md-13 col-lg-13 animated fadeIn">
            <form action="" method="post">
                {{ csrf_field() }}
                <div class="well block good-info">
                    <h3>Отзыв о товаре</h3>
                    <hr class="small" />
                    <div class="form-group {{ $errors->has('text') ? 'has-error' : '' }}">
                        <textarea style="margin-bottom: 8px" rows="3" name="text" class="form-control" placeholder="Напишите отзыв..." required {{ autofocus_on_desktop() }}>{{ old('text') }}</textarea>
                        @if ($errors->has('text'))
                            <span class="help-block">
                                <strong>{{ $errors->first('text') }}</strong>
                            </span>
                        @endif
                    </div>
                    <div class="row form-group {{ $errors->has('shop_rating') ? 'has-error' : '' }}" style="margin-bottom: 2px">
                        <div class="col-sm-8"><label class="control-label">Нравится ли вам магазин?</label></div>
                        <div class="col-xs-6 col-sm-6 text-right text-muted">очень плохой магазин</div>
                        <div class="col-xs-12 col-sm-4 no-padding text-center">
                            <input type="radio" name="shop_rating" value="1" {{ old('shop_rating') === '1' ? 'checked' : '' }}>&nbsp;
                            <input type="radio" name="shop_rating" value="2" {{ old('shop_rating') === '2' ? 'checked' : '' }}>&nbsp;
                            <input type="radio" name="shop_rating" value="3" {{ old('shop_rating') === '3' ? 'checked' : '' }}>&nbsp;
                            <input type="radio" name="shop_rating" value="4" {{ old('shop_rating') === '4' ? 'checked' : '' }}>&nbsp;
                            <input type="radio" name="shop_rating" value="5" {{ old('shop_rating') === '5' || !old('shop_rating') ? 'checked' : '' }}>
                        </div>
                        <div class="col-xs-6 col-sm-6 text-muted">отличный магазин</div>
                    </div>
                    <div class="row form-group {{ $errors->has('dropman_rating') ? 'has-error' : '' }}" style="margin-bottom: 2px">
                        <div class="col-sm-8"><label class="control-label">Хорошо ли сработал курьер?</label></div>
                        <div class="col-xs-6 col-sm-6 text-right text-muted">было сложно найти</div>
                        <div class="col-xs-12 col-sm-4 no-padding text-center">
                            <input type="radio" name="dropman_rating" value="1" {{ old('dropman_rating') === '1' ? 'checked' : '' }}>&nbsp;
                            <input type="radio" name="dropman_rating" value="2" {{ old('dropman_rating') === '2' ? 'checked' : '' }}>&nbsp;
                            <input type="radio" name="dropman_rating" value="3" {{ old('dropman_rating') === '3' ? 'checked' : '' }}>&nbsp;
                            <input type="radio" name="dropman_rating" value="4" {{ old('dropman_rating') === '4' ? 'checked' : '' }}>&nbsp;
                            <input type="radio" name="dropman_rating" value="5" {{ old('dropman_rating') === '5' || !old('dropman_rating') ? 'checked' : '' }}>
                        </div>
                        <div class="col-xs-6 col-sm-6 text-muted">нашлось быстро</div>
                    </div>
                    <div class="row form-group {{ $errors->has('item_rating') ? 'has-error' : '' }}" style="margin-bottom: 4px">
                        <div class="col-sm-8"><label class="control-label">Понравился ли вам стафф?</label></div>
                        <div class="col-xs-6 col-sm-6 text-right text-muted">совсем не понравился</div>
                        <div class="col-xs-12 col-sm-4 no-padding text-center">
                            <input type="radio" name="item_rating" value="1" {{ old('item_rating') === '1' ? 'checked' : '' }}>&nbsp;
                            <input type="radio" name="item_rating" value="2" {{ old('item_rating') === '2' ? 'checked' : '' }}>&nbsp;
                            <input type="radio" name="item_rating" value="3" {{ old('item_rating') === '3' ? 'checked' : '' }}>&nbsp;
                            <input type="radio" name="item_rating" value="4" {{ old('item_rating') === '4' ? 'checked' : '' }}>&nbsp;
                            <input type="radio" name="item_rating" value="5" {{ old('item_rating') === '5' || !old('item_rating') ? 'checked' : '' }}>
                        </div>
                        <div class="col-xs-6 col-sm-6 text-muted">потрясающий стафф</div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Оставить отзыв</button>
                    </div>
                </div>
            </form>
        </div> <!-- /.col-sm-13 -->

        <div class="col-sm-5 col-md-5 col-lg-6 animated fadeIn">
            @include('orders.components.block-review-reminder')
        </div> <!-- /.col-sm-5 -->
    </div> <!-- /.row -->
@endsection