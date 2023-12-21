{{--
This file is part of MM2-dev project.
Description: Paid services page
--}}
@extends('layouts.master')

@section('title', 'Платные услуги')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => 'Платные услуги']
    ]])

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Платные услуги</h3>
                <hr class="small" />
                @if(count($services) > 0)
                    <table class="table table-header" style="margin-bottom: 0">
                        <thead>
                        <tr>
                            <td class="col-xs-15">Описание</td>
                            <td>Стоимость</td>
                            <td class="col-xs-3"></td>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($services as $service)
                            <tr>
                                <td>{{ $service->title }}</td>
                                <td>{{ $service->getHumanPrice() }}</td>
                                <td class="text-right" style="font-size: 15px">
                                    <a class="dark-link hint--top" aria-label="Редактировать" href="{{ url('/shop/management/goods/services/edit/'.$service->id) }}"><i class="glyphicon glyphicon-edit"></i></a>
                                    <a class="text-danger hint--top hint--error" aria-label="Удалить" href="{{ url('/shop/management/goods/services/delete/'.$service->id) }}"><i class="glyphicon glyphicon-remove"></i></a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-info">У магазина еще нет платных услуг.</div>
                @endif
                <hr class="small" />
                <div class="text-center">
                    <a class="btn btn-orange" href="{{ url("/shop/management/goods/services/add") }}">Добавить услугу</a>
                </div>
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-services-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->


@endsection