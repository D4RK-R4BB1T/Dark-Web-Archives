{{-- 
This file is part of MM2-dev project. 
Description: Sidebar of the shop
--}}
<!-- shop/sidebar -->
@include('layouts.components.block-shop', ['shop' => $shop])
@if ($shop->categories_enabled)
    @include('layouts.components.block-categories', ['prefix' => '/shop/' . $shop->slug])
@endif
<!-- / shop/sidebar -->