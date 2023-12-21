{{--
This file is part of MM2-dev project.
Description: Paid service add
--}}
@extends('layouts.master')

@section('title', 'Добавление кастомного места')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => "{$good->title} ($city->title)", 'url' => Auth::user()->can('management-goods-edit', $good)
            ? '/shop/management/goods/packages/' . $good->id
            : NULL],
        ['title' => 'Кастомные места', 'url' => url('/shop/management/goods/places/' . $good->id . '/' . $city->id)],
        ['title' => 'Добавление кастомного места']
    ]])

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Добавление кастомного места: {{ $good->title }}</h3>
                <hr class="small" />
                <form action="" role="form" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="row">
                        @if (in_array($city->id, \App\City::citiesWithRegions()))
                            <div class="col-xs-8">
                                <div class="form-group condensed has-feedback {{ $errors->has('region') ? 'has-error' : '' }}">
                                    <select name="region" class="form-control" title="Округ">
                                        <option value="">Округ</option>
                                        @foreach ($city->regions as $region)
                                            <option value="{{ $region->id }}" {{ old('region') == $region->id ? 'selected' : '' }}>{{ $region->title }}</option>
                                        @endforeach
                                    </select>
                                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                    @if ($errors->has('region'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('region') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="col-xs-{{ in_array($city->id, \App\City::citiesWithRegions()) ? '16' : '24' }}">
                            <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
                                <input id="title" type="text" class="form-control" name="title" placeholder="Название места" value="{{ old('title') }}" required>

                                @if ($errors->has('title'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('title') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-6 -->
                    </div> <!-- /.row -->
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Создать место</button>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-packages-reminder')
        </div> <!-- /.col-sm-6 -->

    </div> <!-- /.row -->
@endsection