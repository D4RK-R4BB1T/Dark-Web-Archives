<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>
<div>
    {{ $orders->links() }}
</div>

<div class="list-group">
    @foreach ($orders as $order)
        {{--<a href="{{ $prefix }}/edit_orders?id={{ $order->id }}" class="list-group-item" id="order_id_{{ $order->id }}">--}}
        <a href="{{ $prefix }}/view_orders?id={{ $order->id }}" class="list-group-item">
            <h4 class="list-group-item-heading">#{{ $order->id }}, Product: <b>{{ $order->good_title }}</b></h4>
            <p class="list-group-item-text">
                @if(array_key_exists($order->user_id, $users))Customer: <b>{{ $users[$order->user_id] }}</b>; @endif
                @if(array_key_exists($order->city_id, $cities))City: <b>{{ $cities[$order->city_id] }}</b>; @endif

                Amount: <b>{{ $order->package_amount }} {{ $order->package_measure }}</b>;
                Price: <b>{{ $order->package_price }} {{ $order->package_currency }}</b>;

                @if($order->package_preorder)
                    Preorder;
                @else
                    Instant;
                @endif

                @if($order->status === 'preorder_paid')
                    Preorder paid
                @elseif($order->status === 'paid')
                    Paid
                @elseif($order->status === 'problem')
                    Have a problem
                @elseif($order->status === 'finished')
                    Finished
                @else
                    Unknown status {{ $order->status }}
                @endif
            </p>
        </a>
        {{--<a href="{{ $prefix }}/delete_{{ $category }}?id={{ $order->id }}" class="list-group-item"><span class="glyphicon glyphicon-minus"> delete</span></a>--}}
    @endforeach
</div>
<div>
    {{ $orders->links() }}
</div>