@extends('layouts.master')

@section('title', 'Заказы')

@section('content')
    <div class="col-sm-7 col-md-5 col-lg-5 col-xs-24">
        @include("shop.service.$service.components.sidebar")
    </div>

    <div class="col-sm-17 col-md-19 col-lg-19 col-xs-24 pull-right animated fadeIn">
        <form action="" method="get">
            <div class="well block">
                @include('orders.components.component-search')

                <h3>Последние заказы</h3>
                <hr class="small" />
                @if ($orders->count() == 0)
                    <hr class="small" />
                    <div class="alert alert-info" style="margin-bottom: 0">Не найдено ни одного заказа.</div>
                @else
                    @foreach ($orders as $order)
                        @php
                        $good = $order->_stub_good();
                        $package = $order->_stub_package();
                        @endphp

                        <div class="panel panel-default
                        {{ $order->status == \App\Order::STATUS_PREORDER_PAID ? 'panel-warning' : '' }}
                        {{ $order->status == \App\Order::STATUS_PROBLEM ? 'panel-danger' : '' }}
                        {{ $order->status == \App\Order::STATUS_FINISHED ? 'panel-success' : '' }}">
                            <div class="panel-heading">
                                <span class="pull-right text-right">
                                    <i class="glyphicon glyphicon-user"></i> {{ $order->user->getPublicName() }}
                                    <span style="margin: 0 10px">|</span>
                                    <i class="glyphicon glyphicon-calendar"></i> {{ $order->created_at->format('d.m.Y в H:i') }}
                                    <br />
                                    {{ $order->getHumanStatus() }}
                                    <span style="margin: 0 10px">|</span>
                                    {{ $package->preorder ? 'Предзаказ' : 'Моментальный' }}
                                    <span style="margin: 0 10px">|</span>
                                    {{ $package->getHumanWeight() }}
                                    <span style="margin: 0 10px">|</span>
                                    {{ $package->getHumanPrice() }}
                                </span>

                                #{{ $order->id }} <strong>{{ $good->title }}</strong> <br />
                                <i class="glyphicon glyphicon-map-marker"></i> {{ $order->city->title }}
                            </div>
                            <div class="panel-body">
                                <p class="text-break-all no-margin">
                                    @if($order->position)
                                        {!! nl2br(e($order->position->quest)) !!}
                                    @else
                                        <i>Либо это предзаказ, либо информация о квесте удалена по сроку давности.</i>
                                    @endif
                                </p>
                                @if ($order->review)
                                    <hr class="small" />
                                    @include('layouts.components.component-review', ['review' => $order->review])
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @if ($orders->total() > $orders->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $orders->appends(request()->input())->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </form>
    </div>
@endsection