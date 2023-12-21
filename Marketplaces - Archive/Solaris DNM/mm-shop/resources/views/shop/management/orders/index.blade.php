{{--
This file is part of MM2-dev project.
Description: Main page of the shop
--}}
@extends('layouts.master')

@section('title', 'Просмотр заказов')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.orders.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            @include('orders.components.component-search')
            <div class="well block good-info">
                <h3>Заказы</h3>
                @if ($orders->count() == 0)
                    <hr class="small" />
                    <div class="alert alert-info" style="margin-bottom: 0">Не найдено ни одного заказа.</div>
                @else
                    <div class="table-responsive">
                    <table class="table table-header" style="margin-bottom: 0">
                        <thead>
                        <tr>
                            <td>Товар</td>
                            <td>Покупатель</td>
                            <td>Дата заказа</td>
                            <td>Стоимость</td>
                            <td>Кол-во</td>
                            <td>Тип заказа</td>
                            <td>Статус</td>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($orders as $order)
                            <?php
                            $good = $order->_stub_good();
                            $package = $order->_stub_package();
                            $packageWithModifiers = $order->_stub_package(true);
                            ?>
                            <tr class="{{ $order->status == \App\Order::STATUS_PREORDER_PAID ? 'bg-warning' : '' }}
                            {{ $order->status == \App\Order::STATUS_PROBLEM ? 'bg-danger' : '' }}
                            {{ $order->status == \App\Order::STATUS_FINISHED ? 'bg-success' : '' }}">
                                <td>
                                    <a href="{{ url('/shop/management/orders/'.$order->id) }}">{{ $good->title }}</a><br />
                                    <i class="glyphicon glyphicon-map-marker"></i> {{ $order->city->title }}
                                </td>
                                <td>{{ $order->user ? $order->user->getPrivateName() : '-'}}</td>
                                <td>{{ $order->created_at->format('d.m.Y в H:i') }}</td>
                                <td>
                                    @if ($order->hasPriceModifiers())
                                        {{ $package->getHumanPrice() }}
                                        <br />
                                        <small class="dashed hint--top" aria-label="Цена для пользователя с учётом групповой скидки / реферальной наценки">({{ $packageWithModifiers->getHumanPrice() }})</small>
                                    @else
                                        {{ $package->getHumanPrice() }}
                                    @endif
                                </td>
                                <td>{{ $package->getHumanWeight() }}</td>
                                <td>
                                    {{ $package->preorder ? 'Предзаказ' : 'Моментальный' }}
                                    @if ($package->preorder && $order->status == \App\Order::STATUS_PREORDER_PAID)
                                        <br />
                                        <span class="text-muted">{{ $order->getHumanPreorderRemainingTime() }}</span>
                                    @endif
                                </td>
                                <td>{{ $order->getHumanStatus() }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                    @if ($orders->total() > $orders->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $orders->appends(request()->input())->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div> <!-- /.col-sm-18 -->
    </div> <!-- /.row -->
@endsection