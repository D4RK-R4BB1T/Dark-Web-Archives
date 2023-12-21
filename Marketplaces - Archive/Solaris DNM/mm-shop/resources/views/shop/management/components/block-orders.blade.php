<?php
$statuses = \App\Order::get(['status']);
$counters = [
    \App\Order::STATUS_PROBLEM => $statuses->filter(function($value) {
        return $value->status === \App\Order::STATUS_PROBLEM;
    })->count(),
    \App\Order::STATUS_PREORDER_PAID => $statuses->filter(function($value) {
        return $value->status === \App\Order::STATUS_PREORDER_PAID;
    })->count(),
    \App\Order::STATUS_PAID => $statuses->filter(function($value) {
        return $value->status === \App\Order::STATUS_PAID;
    })->count(),
    \App\Order::STATUS_FINISHED => $statuses->filter(function($value) {
        return $value->status === \App\Order::STATUS_FINISHED;
    })->count()
];

?>
<!-- shop/management/components/block-orders -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Работа с заказами</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url("/shop/management/orders?") }}" class="list-group-item {{ empty(request('status')) && empty($section) ? 'active' : '' }}">
                <span class="badge gray">{{ count($statuses) }}</span>
                Все заказы
            </a>
            <a href="{{ url('/shop/management/orders?status='.\App\Order::STATUS_PROBLEM) }}" class="list-group-item {{ request('status') === \App\Order::STATUS_PROBLEM ? 'active' : '' }}">
                <span class="badge {{ $counters[\App\Order::STATUS_PROBLEM] > 0 ? 'red' : 'gray' }}">{{ $counters[\App\Order::STATUS_PROBLEM] }}</span>
                Проблема с заказом
            </a>
            <a href="{{ url('/shop/management/orders?status='.\App\Order::STATUS_PREORDER_PAID) }}" class="list-group-item {{ request('status') === \App\Order::STATUS_PREORDER_PAID ? 'active' : '' }}">
                <span class="badge {{ $counters[\App\Order::STATUS_PREORDER_PAID] > 0 ? 'red' : 'gray' }}">{{ $counters[\App\Order::STATUS_PREORDER_PAID] }}</span>
                Ожидают доставки
            </a>
            <a href="{{ url('/shop/management/orders?status='.\App\Order::STATUS_PAID) }}" class="list-group-item {{ request('status') === \App\Order::STATUS_PAID ? 'active' : '' }}">
                <span class="badge gray">{{ $counters[\App\Order::STATUS_PAID] }}</span>
                Ожидают отзыва
            </a>
            <a href="{{ url('/shop/management/orders?status='.\App\Order::STATUS_FINISHED) }}" class="list-group-item {{ request('status') === \App\Order::STATUS_FINISHED ? 'active' : '' }}">
                <span class="badge gray">{{ $counters[\App\Order::STATUS_FINISHED] }}</span>
                Закрытые заказы
            </a>
        </div>
    </div>
</div>
<!-- / shop/management/components/block-orders -->