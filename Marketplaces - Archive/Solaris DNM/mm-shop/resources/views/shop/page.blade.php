{{-- 
This file is part of MM2-dev project. 
Description: Shop page creation
--}}
@extends('layouts.master')

@section('title', $page->title)

@section('content')
    @include('layouts.components.sections-menu')
    @if (Auth::check() && Auth::user()->employee)
        @include('shop.management.components.sections-menu')
    @endif
    @include('layouts.components.sections-breadcrumbs', [
        'breadcrumbs' => [
            // BREADCRUMB_CATALOG,
            // BREADCRUMB_SHOPS,
            ['title' => $shop->title, 'url' => url('/shop/' . $shop->slug)],
            ['title' => $page->title]
        ]
    ])
    @include('shop.sections-pages-menu', [
        'page' => $page->id
    ])
    <div class="row">
        <div class="col-xs-24 animated fadeIn">
            <div class="well block">
                <h3>{{ $page->title }}</h3>
                <hr class="small" />
                <div class="markdown-content">
                    @markdown($page->body)
                </div>

                @if (\Auth::check() && \Auth::user()->employee && \Auth::user()->can('management-sections-pages'))
                    <hr class="small" />
                    <div class="text-center">
                        <a class="btn btn-orange" href="{{ url('/shop/'.$shop->slug.'/pages/edit/'.$page->id) }}">Редактировать страницу</a>
                        &nbsp;
                        <a class="text-muted" href="{{ url('/shop/'.$shop->slug.'/pages/delete/'.$page->id) }}">удалить страницу</a>
                    </div>
                @endif
            </div>
        </div> <!-- /.col-sm-9 -->
    </div> <!-- /.row -->
@endsection