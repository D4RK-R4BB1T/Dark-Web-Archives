{{--
This file is part of MM2-dev project.
Description: Order view page
--}}
@extends('layouts.master')

@section('title', 'Просмотр квеста')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => $good->title, 'url' => url('/shop/management/goods/packages/' . $good->id)],
        ['title' => 'Квесты - ' . $position->package->getHumanWeight(), 'url' => url('/shop/management/goods/quests/' . $good->id . '/' . $position->package_id)],
        ['title' => 'Просмотр квеста']
    ]])
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <form action="" method="post">
                {{ csrf_field() }}
                <div class="well block good-info">
                    <h3>Просмотр квеста</h3>
                    <div class="well">
                        Данный квест был добавлен в магазин пользователем @if($employee = traverse($position, 'employee')){{ $employee->getPrivateName() }}@else - @endif {{ $position->created_at->format('d.m.Y в H:i') }}
                    </div>
                    @if ($position->created_at != $position->updated_at)
                        <div class="well">
                            Данный квест был отредактирован, последнее редактирование: {{ $position->updated_at->format('d.m.Y в H:i') }}
                        </div>
                    @endif
                    <p>{!! nl2br(e($position->quest)) !!}</p>
                </div> <!-- /.col-sm-13 -->
            </form>
        </div>

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-quests-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection