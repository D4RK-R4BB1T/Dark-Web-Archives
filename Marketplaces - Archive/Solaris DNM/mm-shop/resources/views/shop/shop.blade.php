{{-- 
This file is part of MM2-dev project. 
Description: Main page of the shop
--}}
@extends('layouts.master')

@section('title')
@if(config('mm2.application_id') === 'ASTRAL')Купить LSD закладками и почтой@elseКаталог@endif
@endsection

@section('header_scripts')
@if(config('mm2.application_id') === 'ASTRAL')
<meta name="description" content="Купить LSD из Европы, свежие марки оптом и в розницу. Работаем закладками в РФ и почтой по всему СНГ!">
@endif
@endsection

@section('content')
    @include('layouts.components.sections-menu')
    @if (Auth::check() && Auth::user()->employee)
        @include('shop.management.components.sections-menu')
    @endif
    @if ($shop->banner_url)
        <div class="row" style="margin-bottom: 17px">
            <div class="col-xs-24 text-center">
                <img src="{{ url($shop->banner_url) }}" style="max-width: 100%" />
            </div>
        </div>
    @endif
    @if ($shop->information)
        <div class="well block">
            <div class="markdown-content">
                @markdown($shop->information)
            </div>
        </div>
    @endif
    @if (($categoryId = Request::route('categoryId')) !== null)
        @include('layouts.components.sections-breadcrumbs', [
            'breadcrumbs' => [
                // BREADCRUMB_CATALOG,
                // BREADCRUMB_SHOPS,
                ['title' => $shop->title, 'url' => url('/shop/' . $shop->slug)],
                ($categoryId = Request::route('categoryId'))
                    ? ['title' => \App\Category::findOrFail($categoryId)->title]
                    : null
            ],
            'left_column_width' => [6, 7, 5, 5] // col-xs-6 col-sm-7 col-md-5 col-lg-5
        ])
    @endif
    @include('shop.sections-pages-menu', [
        'page' => 'shop'
    ])
    <div class="row">
        <div class="col-sm-7 col-md-5 col-lg-5">
            @include('shop.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-17 col-md-19 col-lg-19 animated fadeIn">
            @if ($shop->search_enabled)
                @include('layouts.components.component-search')
            @endif
            @if(count($goods) > 0)
                @foreach($goods->chunk(4) as $chunk)
                    <div class="row">
                        @foreach($chunk as $good)
                            @include('layouts.components.component-card', ['good' => $good])
                        @endforeach
                    </div>
                @endforeach
            @else
                <div class="alert alert-info">Не найдено ни одного товара соответствующего заданным критериям.</div>
            @endif
        </div> <!-- /.col-sm-9 -->
    </div> <!-- /.row -->

@endsection
