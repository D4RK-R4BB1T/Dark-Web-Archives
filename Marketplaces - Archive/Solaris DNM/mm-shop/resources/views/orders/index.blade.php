{{--
This file is part of MM2-dev project.
Description: Orders list page
--}}
@extends('layouts.master')

@section('title', 'Заказы')

@section('content')
    @include('layouts.components.sections-menu')

    <div class="row">
        <div class="col-xs-24 col-sm-18 col-md-18 col-lg-19 pull-right animated fadeIn">
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
                                <td>Продавец</td>
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
                                $package = $order->_stub_package(true);
                                ?>
                                <tr>
                                    <td><a href="{{ url('/orders/'.$order->id) }}">{{ $good->title }}</a></td>
                                    <td><a href="{{ url('/shop/'.$order->shop->slug) }}">{{ $order->shop->title }}</a></td>
                                    <td>{{ $order->created_at->format('d.m.Y в H:i') }}</td>
                                    <td>{{ $package->getHumanPrice() }}</td>
                                    <td>{{ $package->getHumanWeight() }}</td>
                                    <td>{{ $package->preorder ? 'Предзаказ' : 'Моментальный' }}</td>
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
            </div> <!-- /.col-sm-13 -->
        </div>
        <div class="col-xs-24 col-sm-6 col-md-6 col-lg-5 pull-left">
            @include('orders.components.block-orders')
        </div> <!-- /.col-lg-5 -->
    </div> <!-- /.row -->
@endsection