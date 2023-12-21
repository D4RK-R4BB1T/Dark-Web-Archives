{{--
This file is part of MM2-dev project.
Description: Menu with shop pages
--}}
<!-- shop/sections-pages-menu -->
<ul class="pages-menu nav nav-pills">
    <li class="{{ $page === 'shop' ? 'active' : ''}}"><a href="{{ url("/shop/".$shop->slug) }}">Главная</a></li>
    @foreach ($shop->pages as $shopPage)
        <li class="{{ $page === $shopPage->id ? 'active' : '' }}"><a href="{{ url('/shop/'.$shop->slug.'/pages/'.$shopPage->id) }}">{{ $shopPage->title }}</a></li>
    @endforeach
    @if (Auth::check() && Auth::user()->employee && Auth::user()->can('management-sections-pages'))
        <li class="{{ $page === 'page-add' ? 'active' : ''  }}"><a href="{{ url('/shop/'.$shop->slug.'/pages/add') }}"><i class="glyphicon glyphicon-plus-sign"></i> Добавить страницу</a></li>
    @endif
</ul>
<br />
<!-- / shop/sections-pages-menu -->