{{--
This file is part of MM2-dev project.
Description: Main page of the shop
--}}
@extends('layouts.master')

@section('title', 'Удаление упаковки')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => "{$good->title} ({$package->city->title})", 'url' => url('/shop/management/goods/packages/' . $good->id)],
        ['title' => 'Упаковки', 'url' => url('/shop/management/goods/packages/city/' . $good->id . '/' . $package->city->id)],
        ['title' => 'Удаление упаковки']
    ]])
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <form action="" method="post">
                {{ csrf_field() }}
                <div class="well block good-info">
                    <h3>Удаление упаковки</h3>
                    <hr class="small" />
                    <p>Вы действительно хотите удалить данную упаковку? Все добавленные квесты будут удалены. Данная операция необратима.</p>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Подтвердить</button>
                        &nbsp;
                        <a class="text-muted" href="{{ URL::previous() }}">вернуться назад</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-packages-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->


@endsection