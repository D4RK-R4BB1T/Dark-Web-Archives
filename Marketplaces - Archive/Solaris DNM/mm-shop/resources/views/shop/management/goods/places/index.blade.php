{{--
This file is part of MM2-dev project.
Description: Custom places page
--}}
@extends('layouts.master')

@section('title', 'Кастомные места')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => "{$good->title} ($city->title)", 'url' => url('/shop/management/goods/packages/' . $good->id)],
        ['title' => 'Кастомные места']
    ]])
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Кастомные места: {{ $good->title }}</h3>
                <hr class="small" />
                @if(count($customPlaces) > 0)
                    <table class="table table-header" style="margin-bottom: 0">
                        <thead>
                        <tr>
                            <td class="col-xs-7">Район</td>
                            <td>Место</td>
                            <td class="col-xs-3"></td>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($customPlaces as $place)
                            <tr>
                                <td>{{ traverse($place, 'region->title') ?: '-' }}</td>
                                <td>{{ $place->title }}</td>
                                <td class="text-right" style="font-size: 15px">
                                    <a class="dark-link hint--top" aria-label="Редактировать" href="{{ url('/shop/management/goods/places/edit/'.$good->id.'/'.$place->id) .'/'. $city->id }}"><i class="glyphicon glyphicon-edit"></i></a>
                                    <a class="text-danger hint--top hint--error" aria-label="Удалить" href="{{ url('/shop/management/goods/places/delete/'.$good->id.'/'.$place->id.'/'.$city->id) }}"><i class="glyphicon glyphicon-remove"></i></a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-info">У товара еще нет кастомных мест.</div>
                @endif
                <hr class="small" />
                <div class="text-center">
                    <a class="btn btn-orange" href="{{ url('/shop/management/goods/places/add/'.$good->id.'/'.$city->id) }}">Добавить кастомное место</a>
                    &nbsp;
                    <a class="text-muted" href="{{ url('/shop/management/goods/packages/city/'.$good->id.'/'.$city->id) }}">вернуться назад</a>
                </div>
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-places-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection