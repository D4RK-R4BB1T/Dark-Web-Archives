{{-- 
This file is part of MM2-dev project. 
Description: Shop page creation
--}}
@extends('layouts.master')

@section('title', 'Удаление страницы :: ' . $page->title)

@section('content')
    @include('layouts.components.sections-menu')
    @if (Auth::user()->employee)
        @include('shop.management.components.sections-menu')
    @endif
    @include('layouts.components.sections-breadcrumbs', [
        'breadcrumbs' => [
            // BREADCRUMB_CATALOG,
            // BREADCRUMB_SHOPS,
            ['title' => $shop->title, 'url' => url('/shop/' . $shop->slug)],
            ['title' => $page->title, 'url' => url('/shop/' . $shop->slug . '/pages/' . $page->id)],
            ['title' => 'Удаление страницы']
        ]
    ])
    @include('shop.sections-pages-menu', [
        'page' => $page->id
    ])
    <div class="row">
        <div class="col-sm-24 animated fadeIn">
            <form role="form" action="" method="post">
                {{ csrf_field() }}
                <div class="well block">
                    <h3>Удаление страницы</h3>
                    <hr class="small" />
                    <p>
                        Вы действительно хотите удалить страницу {{ $page->title }}? Данная операция необратима.
                    </p>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Удалить страницу</button>
                        &nbsp;
                        <a class="text-muted" href="{{ URL::previous() }}">вернуться назад</a>
                    </div>
                </div>
            </form>
        </div> <!-- /.col-sm-9 -->
    </div> <!-- /.row -->
@endsection