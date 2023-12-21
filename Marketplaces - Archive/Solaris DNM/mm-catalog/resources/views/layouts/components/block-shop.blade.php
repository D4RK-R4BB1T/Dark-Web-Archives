<?php
$shop = isset($shop) ? $shop : null;
if (!$shop) {
    throw new \Exception(__('shop.Shop is not set'));
}
?>
<!-- layouts/components/block-shop -->
<!-- shop: {{ $shop->slug }} -->
<div class="panel panel-default panel-sidebar shop-info block no-padding">
    <div class="panel-heading">{{ __('shop.Seller') }}</div>
    <div class="panel-body text-xs-center">
        <div class="hidden-xs"><img src="{{ $shop->avatar() }}" class="img-responsive" /></div>
        <a target="_blank" href="{{ catalog_jump_url($shop->id, '/') }}" class="dark-link"><h5><i class="glyphicon glyphicon-user" style="width: 14px"></i> {{ $shop->title }}</h5></a>
        <p class="text-muted"><i class="glyphicon glyphicon-lock" style="width: 14px;"></i> {{ __('shop.Verified seller') }}</p>
        {{--<p>@include('shop.sections-rating', ['rating' => 3]) 2000+ сделок</p>--}}
        {{--<p style="font-size: 90%;"><a class="text-muted" href="#">открыть все отзывы о продавце</a></p>--}}
        <p><i class="glyphicon glyphicon-envelope" style="width: 14px;"></i> <a target="_blank" href="{{ catalog_jump_url($shop->id, '/message') }}">{{ __('shop.Contact the seller') }}</a></p>
    </div>
</div>
<!-- /layouts/components/block-shop -->