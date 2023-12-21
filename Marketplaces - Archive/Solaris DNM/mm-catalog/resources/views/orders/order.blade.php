{{--
This file is part of MM2-dev project.
Description: Order view page
--}}
@extends('layouts.master')

@section('title', __('orders.View order'))

@section('content')
    @include('layouts.components.sections-breadcrumbs', [
        'breadcrumbs' =>
        [
            BREADCRUMB_ORDERS,
            ['title' => __('orders.View order', ['order' => $order->app_order_id ])]
        ]
    ])

    <div class="row">
        <div class="col-xs-24 col-sm-13 col-sm-pull-5 col-md-13 col-lg-pull-6 col-lg-13 pull-right animated fadeIn">
            <div class="well block good-info">
                <h3>{{ __('orders.View order', ['order' => $order->app_order_id ]) }}</h3>
                <hr class="small" />
                @if ($order->status === \App\Order::STATUS_PREORDER_PAID)
                    <p>{{ __('orders.Your order is processing') }}</p>
                    <p><i class="text-muted">{{ __('orders.Order will not be ready in', ['humanTime' => $order->getHumanPreorderRemainingTime()]) }}</i></p>
                @else
                    @if ($order->getQuestRemainingTime() > 0)
                        <p>{!! nl2br(e(traverse($order, 'position->quest') ?: '-')) !!}</p>
                        <p><i class="text-muted">{{ __('orders.Place will be shown for', ['humanTime' => $order->getHumanQuestRemainingTime() ]) }}</i></p>
                    @else
                        <p><i class="text-muted">{{ __('orders.Place is hidden') }}</i></p>
                    @endif
                    @if (in_array($order->status, [\App\Order::STATUS_PAID, \App\Order::STATUS_FINISHED_AFTER_DISPUTE, \App\Order::STATUS_CANCELLED_AFTER_DISPUTE]))
                        <hr class="small" />
                        <div class="text-center">
                            <a class="btn btn-orange" target="_blank" href="{{ catalog_jump_url($order->shop->id, '/orders/review/' . $order->app_order_id, true) }}">{{ __('orders.Leave a review') }}</a>
                            &nbsp;
                            <a class="text-muted" target="_blank" href="{{ catalog_jump_url($order->shop->id, '/orders/problem/' . $order->app_order_id, true) }}"><i class="glyphicon glyphicon-question-sign"></i> {{ __('orders.Problem with order') }}</a>
                        </div>
                    @endif
                    @if ($order->status === \App\Order::STATUS_PROBLEM)
                        <hr class="small" />
                        <div class="text-center">
                            <a class="btn btn-orange" target="_blank" href="{{ catalog_jump_url($order->shop->id, '/orders/review/' . $order->app_order_id, true) }}">{{ __('orders.Leave a review') }}</a>
                        </div>
                    @endif
                @endif
            </div> <!-- /.col-sm-13 -->

            @if ($order->review)
                <div class="well block">
                    <h3>Review</h3>
                    <hr class="small" />
                    @include('layouts.components.component-review', ['review' => $order->review])
                </div>
            @endif
        </div>

        <div class="col-xs-24 col-sm-6 col-md-6 col-lg-5 pull-left">
            @include('orders.components.block-good')
            @include('layouts.components.block-shop', ['shop' => $order->shop])
        </div>

        <div class="col-xs-24 col-sm-5 col-sm-push-13 col-md-5 col-lg-6 animated fadeIn">
            @include('orders.components.block-receive-reminder')
        </div> <!-- /.col-sm-5 -->
    </div> <!-- /.row -->
@endsection