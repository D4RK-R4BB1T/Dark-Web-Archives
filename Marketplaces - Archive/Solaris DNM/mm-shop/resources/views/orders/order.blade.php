{{--
This file is part of MM2-dev project.
Description: Order view page
--}}
@extends('layouts.master')

@section('title', 'Просмотр заказа')

@section('content')
    @include('layouts.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
        'breadcrumbs' =>
        [
            BREADCRUMB_ORDERS,
            ['title' => 'Просмотр заказа']
        ]
    ])

    @if($errors->any())
        <div class="alert alert-warning">
            <strong>Отзыв не принят:</strong> <br />
            @foreach($errors->all() as $error)
                {{ $error }}
                @if (!$loop->last)<br />@endif
            @endforeach
        </div>
    @endif

    <div class="row">
        <div class="col-xs-24 col-sm-13 col-sm-pull-5 col-md-13 col-lg-pull-6 col-lg-13 pull-right animated fadeIn">
            <div class="well block good-info">
                <h3>Просмотр заказа</h3>
                <hr class="small" />
                @if ($order->status === \App\Order::STATUS_PREORDER_PAID)
                    @if (count($services = $order->_stub_services()) > 0)
                        <div class="well">
                            Были заказаны следующие дополнительные услуги:
                            <ul style="margin-bottom: 0">
                                @foreach($services as $service)
                                    <li>{{ $service->title }} - {{ $service->getHumanPrice() }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <p>Ваш заказ собирается.</p>
                    <p><i class="text-muted">Если через {{ $order->getHumanPreorderRemainingTime() }} заказ не будет готов, деньги будут возвращены автоматически.</i></p>
                @elseif ($order->status === \App\Order::STATUS_QIWI_PAID)
                    <p><i class="text-muted">Оплата заказа проверяется, пожалуйста, подождите.</i></p>
                @elseif ($order->status === \App\Order::STATUS_QIWI_RESERVED)
                    @if ($order->getReservationRemainingTime() > 0)
                        <form role="form" action="" method="post" class="form-horizontal">
                            {{ csrf_field() }}
                            <div class="alert alert-info" style="margin-bottom: 0">
                                Необходимо оплатить заказ. <br />
                                Резерв будет автоматически отменен через {{ $order->getHumanReservationRemainingTime() }}.
                            </div>
                            <hr class="small" />
                            <div class="form-group">
                                <label class="col-xs-6 control-label">Сумма к оплате:</label>
                                <div class="col-xs-18">
                                    <p class="form-control-static">{{ human_price($order->qiwiTransaction->amount, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-6 control-label">Кошелек:</label>
                                <div class="col-xs-18">
                                    <input type="text" class="form-control" value="{{ traverse($order, 'qiwiTransaction->qiwiWallet->login') ?: '-' }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-6 control-label">Комментарий:</label>
                                <div class="col-xs-18">
                                    <input type="text" class="form-control" value="{{ $order->qiwiTransaction->comment }}">
                                    <span class="help-block">
                                        Важно переводить деньги именно с таким примечанием, иначе платеж не будет обработан автоматически.
                                        Платеж можно отправить в несколько частей, в том числе с разных кошельков. <br />
                                    </span>
                                </div>
                            </div>
                            <hr class="small" />
                            <div class="text-center">
                                <span class="text-muted">
                                    После совершения оплаты нажмите на кнопку ниже. <br />
                                    Не нажимайте на кнопку без совершения оплаты - это может привести к блокировке аккаунта.
                                </span>
                            </div>
                            <hr class="small" />
                            <div class="text-center">
                                <button type="submit" class="btn btn-orange">Проверить оплату</button>
                            </div>
                        </form>
                    @else
                        <p><i class="text-muted">Срок резерва истек, заказ скоро будет отменен.</i></p>
                    @endif
                @else
                    @if ($order->getQuestRemainingTime() > 0)
                        <p>{!! nl2br(e(traverse($order, 'position->quest') ?: '-')) !!}</p>
                        <p><i class="text-muted">Место будет показываться еще {{ $order->getHumanQuestRemainingTime() }}.</i></p>
                    @else
                        <p><i class="text-muted">Место скрыто.</i></p>
                    @endif
                    @if ($order->status === \App\Order::STATUS_PAID)
                        <hr class="small" />
                        <div class="text-center">
                            @component('layouts.components.component-modal-toggle', ['id' => 'orders-review', 'class' => 'btn btn-orange'])
                                Оставить отзыв
                            @endcomponent
                            &nbsp;
                            <a class="text-muted" href="{{ url('/orders/problem/'.$order->id) }}"><i class="glyphicon glyphicon-question-sign"></i> Проблема с заказом?</a>
                        </div>
                    @endif
                    @if ($order->status === \App\Order::STATUS_PROBLEM)
                        <hr class="small" />
                        <div class="text-center">
                            @component('layouts.components.component-modal-toggle', ['id' => 'orders-review', 'class' => 'btn btn-orange'])
                                Оставить отзыв
                            @endcomponent
                        </div>
                    @endif
                @endif
            </div> <!-- /.col-sm-13 -->

            @if ($order->review)
                <div class="well block">
                    <div class="row">
                        <div class="col-xs-12">
                            <h3 class="no-margin">Отзыв</h3>
                        </div>

                        <div class="col-xs-12 text-right margin-top-6">
                            @if($order->review->getEditRemainingTime() > 0 && $order->review->getLastEditTime() >= config('mm2.review_edit_time_every'))
                                <a href="{{ url('/orders/review/edit/' . $order->id) }}">
                                    <i class="glyphicon glyphicon-pencil"></i> Отредактировать отзыв
                                </a>
                            @endif
                        </div>
                    </div>

                    <hr class="small" />
                    @include('layouts.components.component-review', ['review' => $order->review])
                </div>
            @endif
        </div>

        <div class="col-xs-24 col-sm-6 col-md-6 col-lg-5 pull-left">
            @if ($order->status !== \App\Order::STATUS_QIWI_RESERVED && $order->status !== \App\Order::STATUS_QIWI_PAID)
                @include('orders.components.block-good', ['include_referrer_fee' => true])
            @endif
            @include('layouts.components.block-shop', ['shop' => $order->shop])
        </div>

        <div class="col-xs-24 col-sm-5 col-sm-push-13 col-md-5 col-lg-6 animated fadeIn">
            @include('orders.components.block-receive-reminder')
        </div> <!-- /.col-sm-5 -->
    </div> <!-- /.row -->
@endsection

@section('modals')
    @if ($order->status === \App\Order::STATUS_PAID || $order->status === \App\Order::STATUS_PROBLEM)
        @include('orders.components.modals.orders-review')
    @endif
@endsection