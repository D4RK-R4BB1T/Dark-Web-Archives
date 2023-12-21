{{--
This file is part of MM2-dev project.
Description: Order view page
--}}
@extends('layouts.master')

@section('title', 'Просмотр заказа')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_ORDERS,
        ['title' => 'Просмотр заказа']
    ]])
    <div class="row">
        <div class="col-sm-6 col-md-6 col-lg-5">
            @include('orders.components.block-good', ['include_referrer_fee' => true, 'include_referrer_hint' => true, 'include_group_discount'=> true])
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-13 col-md-13 col-lg-13 animated fadeIn">
            <form action="" method="post">
                {{ csrf_field() }}
                <div class="well block good-info">
                    <h3>Просмотр заказа</h3>
                    <hr class="small" />
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

                    @if ($order->comment)
                        <div class="well">
                            Комментарий к заказу: <br />
                            {!! nl2br(e($order->comment)) !!}
                        </div>
                    @endif

                    @if ($order->status === \App\Order::STATUS_PREORDER_PAID)
                        <div class="well">
                            Необходимо добавить квест!
                        </div>
                        <p><i class="text-muted">Если через {{ $order->getHumanPreorderRemainingTime() }} заказ не будет готов, деньги будут автоматически возвращены покупателю.</i></p>
                        <div class="form-group {{ $errors->has('quest') ? 'has-error' : '' }}">
                            <textarea name="quest" class="form-control" rows="3" placeholder="Напишите текст квеста в это поле..." required {{ autofocus_on_desktop() }}>{{ old('quest') }}</textarea>
                            @if ($errors->has('quest'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('quest') }}</strong>
                                </span>
                            @endif
                        </div>
                        <div>
                            <button type="submit" class="btn btn-orange">Добавить квест</button>

                            @if (\Auth::user()->can('management-sections-orders') && \Auth::user()->can('management-quests-preorder'))
                                @include('shop.management.components.component-extend-preorder-time')
                            @endif
                        </div>
                    @else
                        <div class="well">
                            Данный товар был добавлен в магазин пользователем @if($employee = traverse($order, 'position->employee')){{ $employee->getPrivateName() }} @else '-' @endif {{ traverse($order, 'position->created_at->format("d.m.Y H:i")') ?: '-' }}
                        </div>
                        @if ($order->status_was_problem)
                            <div class="well">
                                Этот заказ был отмечен проблемным.
                                @if ($order->thread)
                                    @if (Auth::user()->can('management-sections-messages') && $order->thread->hasParticipant(-$order->shop_id))
                                        <a href="{{ url('/shop/management/messages/'.$order->thread->id) }}" class="dark-link">Перейти к диалогу.</a>
                                    @elseif ($order->position && $order->position->employee_id == Auth::user()->employee->id && $order->thread->hasParticipant(Auth::user()->employee->user_id)) {{-- looks shitty, sorry for that --}}
                                        <a href="{{ url('/messages/'.$order->thread->id) }}" class="dark-link">Перейти к диалогу.</a>
                                    @endif
                                @endif
                            </div>
                        @endif

                        @if (Auth()->user()->can('management-sections-own-orders') && $order->courier_fined)
                            <div class="well">
                                Курьер получил штраф за ненаход этого заказа.
                            </div>
                        @endif

                        @if(Auth()->user()->can('management-sections-orders') && !is_null($order->package) && $order->package->employee_penalty > 0 && in_array($order->status, [\App\Order::STATUS_PAID, \App\Order::STATUS_PROBLEM]) && !$order->courier_fined)
                            <div class="well">
                                <a href="{{ url("/shop/management/orders/".$order->id."/notfound?_token=".csrf_token()) }}">
                                    <i class="glyphicon glyphicon-alert"></i>
                                    Отметить ненайденным
                                </a>
                                <br />
                                <span class="help-block">
                                    Курьер получит штраф, равный указанному в упаковке товара: {{ human_price(-$order->package->employee_penalty, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }} Запись будет отображена в ленте действий сотрудника.
                                </span>
                            </div>
                        @endif

                        <p>{!! nl2br(e(traverse($order, 'position->quest') ?: '-')) !!}</p>
                    @endif
                </div> <!-- /.col-sm-13 -->

                @if ($order->review)
                    <div class="well block">
                        <h3>Отзыв</h3>
                        <hr class="small" />
                        @include('layouts.components.component-review', ['review' => $order->review])
                    </div>
                @endif

                @if (\Auth::user()->can('management-sections-orders'))
                    @include('shop.management.components.component-user', ['user' => $order->user, 'title' => 'Заказ #' . $order->id])
                @endif
            </form>
        </div>

        <div class="col-sm-5 col-md-5 col-lg-6 animated fadeIn">
            @include('orders.components.block-receive-reminder')
        </div> <!-- /.col-sm-5 -->
    </div> <!-- /.row -->

    @if (\Auth::user()->can('management-sections-orders') && \Auth::user()->can('management-quests-preorder'))
        @include('shop.management.components.modals.order-extend', ['orderId' => $order->id, 'steps' => $preorderTimeExtendSteps])
    @endif
@endsection
