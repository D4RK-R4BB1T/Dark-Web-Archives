{{--
This file is part of MM2-dev project.
Description: Main page of the shop
--}}
@extends('layouts.master')

@section('title', 'Добавление квеста')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => $good->title]
    ]])
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            <div class="well block">
                <h3>Добавление квеста: {{ $good->title }}</h3>
                <hr class="small"/>
                @if(count($cities) > 0)
                    <h4>Выберите город:</h4>
                    <div class="list-group">
                        @foreach($cities as $city)
                            <a href="{{ url('/shop/management/goods/quests/add/'.$good->id.'/'.$city->id) }}" class="list-group-item">{{ $city->title }}</a>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info">Не заданы города для данного товара.</div>
                @endif
                @can('management-goods-edit', $good)
                    <hr class="small" />
                    <div class="text-center">
                        <a class="btn btn-orange" href="{{ url('/shop/management/goods/cities/'.$good->id) }}">Управление городами</a>
                    </div>
                @endcan
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->

        {{--<div class="col-sm-6 animated fadeIn">--}}
        {{--@include('shop.management.components.block-goods-packages-reminder')--}}
        {{--</div> <!-- /.col-sm-6 -->--}}
    </div> <!-- /.row -->


@endsection