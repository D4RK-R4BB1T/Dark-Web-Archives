{{--
This file is part of MM2-dev project.
Description: Main page of the shop
--}}
@extends('layouts.master')

@section('title', 'Список отзывов')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.orders.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            <div class="well block good-info">
                <h3>Отзывы
                    @if ($selectedUser) пользователя {{ $selectedUser->getPublicName() }} @endif
                </h3>
                <hr class="small" />
                @if (!$selectedUser)
                    <p>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Количество отзывов:</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                            {{ $reviews->total() }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Средняя оценка магазина:</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                            {{ $rating = $shop->getRating() }}
                            &nbsp;
                            @include('shop.sections-rating', ['rating' => $rating])
                        </div>
                    </div>
                    </p>
                @endif
                <hr class="small" />
                @if ($reviews->count() == 0)
                    <div class="alert alert-info" style="margin-bottom: 0">Не найдено ни одного отзыва.</div>
                @else
                    @foreach ($reviews as $review)
                        <div class="row">
                            <div class="col-md-9">
                                @if ($review->order)
                                    <?php
                                    $package = $review->order->_stub_package();
                                    $good = $review->order->_stub_good();
                                    ?>
                                    <h4 style="margin-bottom: 4px; margin-top: 0px">
                                        @if ($review->good)
                                            <a target="_blank" href="{{ url('/shop/'.$shop->slug.'/goods/'.$review->good->id) }}">{{ $good->title }}</a>
                                        @else
                                            {{ $good->title }}
                                        @endif
                                    </h4>
                                    <h4 style="margin-bottom: 4px; margin-top: 0">
                                        {{ $package->getHumanWeight() }} / <span class="text-muted">{{ $package->getHumanPrice() }}</span>
                                    </h4>
                                    <i class="glyphicon glyphicon-map-marker"></i>&nbsp;&nbsp;{{ $review->order->city->title }} <br />
                                    <i class="glyphicon glyphicon-user"></i>&nbsp;&nbsp;{{ $review->user->getPublicName() }}
                                        (
                                        @if (Auth::user()->can('management-sections-messages'))
                                            <a style="top: 1px" class="hint--top" aria-label="Отправить сообщение" href="{{ url('/shop/management/messages/new?user='.$review->user->id) }}&title={{ urlencode('Отзыв к заказу #' . $review->order_id) }}"><i class="glyphicon glyphicon-envelope"></i></a> /
                                        @endif
                                        <a class="hint--top dashed" aria-label="Количество покупок" href="{{ url('/shop/management/orders?user='.$review->user->id) }}"><strong>{{ $review->user->buy_count }}</strong></a> /
                                        <a href="{{ url('/shop/management/orders/reviews?user='.$review->user->id) }}" class="dashed hint--top" aria-label="Количество оставленных отзывов"><strong>{{ $review->user->goodsReviews()->count() }}</strong></a>
                                        )
                                    <br />
                                        <span class="text-muted">{!! \App\Packages\Utils\Formatters::formatReview($review->user->note) !!}</span>
                                @else
                                    <p class="text-muted">Заказ не найден</p>
                                @endif
                                <div class="hidden-lg hidden-md">
                                    <hr class="small" />
                                </div>
                            </div>
                            <div class="col-md-15">
                                @include('layouts.components.component-review', ['review' => $review])
                            </div>
                        </div>
                        @if (!$loop->last)
                            <hr class="small" />
                        @endif
                    @endforeach
                    @if ($reviews->total() > $reviews->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $reviews->appends(request()->input())->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div> <!-- /.col-sm-18 -->
    </div> <!-- /.row -->
@endsection