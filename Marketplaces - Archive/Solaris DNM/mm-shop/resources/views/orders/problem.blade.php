{{--
This file is part of MM2-dev project.
Description: Order view page
--}}
@extends('layouts.master')

@section('title', 'Проблема с заказом')

@section('content')
    @include('layouts.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
        'breadcrumbs' =>
        [
            BREADCRUMB_ORDERS,
            ['title' => 'Просмотр заказа', 'url' => url('/orders/' . $order->id)],
            ['title' => 'Проблема с заказом']
        ]
    ])

    <div class="row">
        <div class="col-sm-6 col-md-6 col-lg-5">
            @include('orders.components.block-good', ['include_referrer_fee' => true])
            @include('layouts.components.block-shop', ['shop' => $order->shop])
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-13 col-md-13 col-lg-13 animated fadeIn">
            @if ($order->shop->problem)
                <div class="well block">
                    <h3>Информация от продавца</h3>
                    <hr class="small" />
                    <div class="markdown-content">
                        @markdown($order->shop->problem)
                    </div>
                </div>
            @endif

            <form action="" method="post">
                {{ csrf_field() }}
                <div class="well block good-info">
                    <h3>Проблема с заказом</h3>
                    <hr class="small" />
                    <p>
                        Перевести заказ в статус "проблемный" и начать общение с продавцом?
                    </p>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Продолжить</button>
                        &nbsp;
                        <a class="text-muted" href="{{ URL::previous() }}">вернуться назад</a>
                    </div>
                </div> <!-- /.col-sm-13 -->
            </form>
        </div>

        <div class="col-sm-5 col-md-5 col-lg-6 animated fadeIn">
            @include('orders.components.block-receive-problem-reminder')
        </div> <!-- /.col-sm-5 -->
    </div> <!-- /.row -->
@endsection