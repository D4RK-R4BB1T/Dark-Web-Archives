{{--
This file is part of MM2-dev project.
Description: Order view page
--}}
@extends('layouts.master')

@section('title', 'Удаление квеста')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => $good->title, 'url' => url('/shop/management/goods/packages/' . $good->id)],
        ['title' => 'Квесты - ' . $position->package->getHumanWeight(), 'url' => url('/shop/management/goods/quests/' . $good->id . '/' . $position->package_id)],
        ['title' => 'Удаление квеста']
    ]])

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <form action="" method="post">
                {{ csrf_field() }}
                <div class="well block good-info">
                    <h3>Удаление квеста</h3>
                    <hr class="small" />
                    <p>Вы действительно хотите удалить данный квест? Данная операция необратима.</p>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Удалить</button>
                        &nbsp;
                        <a class="text-muted" href="{{ URL::previous() }}">вернуться назад</a>
                    </div>
                </div> <!-- /.col-sm-13 -->
            </form>
        </div>

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-quests-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection