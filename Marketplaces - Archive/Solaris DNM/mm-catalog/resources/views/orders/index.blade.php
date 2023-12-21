{{--
This file is part of MM2-dev project.
Description: Orders list page
--}}
@extends('layouts.master')

@section('title', 'Заказы')

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-18 col-md-18 col-lg-19 pull-right animated fadeIn">
            <div class="well block good-info">
                <h3>Заказы</h3>
                @if ($orders->count() == 0)
                    <hr class="small" />
                    <div class="alert alert-info" style="margin-bottom: 0">{{ __('orders.No orders found') }}</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>{{ __('goods.number') }}</td>
                                <td>{{ __('goods.Goods') }}</td>
                                <td>{{ __('layout.Seller') }}</td>
                                <td>{{ __('layout.Date') }}</td>
                                <td>{{ __('layout.Price') }}</td>
                                <td>{{ __('layout.Amount') }}</td>
                                <td>{{ __('layout.Type') }}</td>
                                <td>{{ __('orders.Status') }}</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($orders as $order)
                                <?php
                                $good = $order->_stub_good();
                                $package = $order->_stub_package();
                                ?>
                                <tr>
                                    <td>{{ $order->app_order_id }}</td>
                                    <td><a href="/orders/{{ $order->id }}">{{ $good->title }}</a></td>
                                    <td><a target="_blank" href="{{ catalog_jump_url($order->shop->id, '/') }}">{{ $order->shop->title }}</a></td>
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