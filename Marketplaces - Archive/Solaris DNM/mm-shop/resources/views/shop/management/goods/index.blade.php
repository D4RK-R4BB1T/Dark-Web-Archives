{{--
This file is part of MM2-dev project.
Description: Main page of the shop
--}}
@extends('layouts.master')

@section('title', 'Просмотр товаров')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            @include('layouts.components.component-search', ['show_all' => true])
            @if(count($goods) > 0)
                @foreach($goods->chunk(4) as $chunk)
                    <div class="row">
                        @foreach($chunk as $good)
                            @include('shop.management.components.component-card-manage', ['good' => $good])
                        @endforeach
                    </div>
                @endforeach
            @else
                <div class="alert alert-info">Не найдено ни одного товара.</div>
            @endif
        </div> <!-- /.col-sm-9 -->
    </div> <!-- /.row -->
@endsection