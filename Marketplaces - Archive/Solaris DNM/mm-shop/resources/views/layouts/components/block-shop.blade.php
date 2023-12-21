@php
use app\User;

$shop = isset($shop) ? $shop : null;
if (!$shop) {
    throw new \Exception('Shop is not set.');
}

if(Auth::check()) {
    $user = Auth::user()->load(['orders']);
}
@endphp
<!-- layouts/components/block-shop -->
<!-- shop: {{ $shop->slug }} -->
<div class="panel panel-default panel-sidebar shop-info block no-padding">
    <div class="panel-heading">Продавец</div>
    <div class="panel-body text-xs-center">
        <div class="hidden-xs"><img src="{{ url($shop->avatar()) }}" class="img-responsive" /></div>
        <a href="{{ url('/shop/'.$shop->slug) }}" class="dark-link"><h5><i class="glyphicon glyphicon-user" style="width: 14px"></i> {{ $shop->title }}</h5></a>
        <p class="text-muted"><i class="glyphicon glyphicon-lock" style="width: 14px;"></i> Проверенный продавец</p>
        {{--<p>@include('shop.sections-rating', ['rating' => 3]) 2000+ сделок</p>--}}
        {{--<p style="font-size: 90%;"><a class="text-muted" href="#">открыть все отзывы о продавце</a></p>--}}
        @auth
            @if(!$user->employee && in_array($user->role, [User::ROLE_USER, User::ROLE_CATALOG]) && ($user->buy_count < 1 && $user->orders->count() < 1))
                <p><i class="glyphicon glyphicon-info-sign" style="width: 14px;"></i> Чтобы написать продавцу, совершите хотя бы одну покупку.</p>
            @else
                <p><i class="glyphicon glyphicon-envelope" style="width: 14px;"></i> <a href="{{ url('/shop/'.$shop->slug.'/message') }}">Связаться с продавцом</a></p>
            @endif
        @endauth
    </div>
</div>
<!-- /layouts/components/block-shop -->