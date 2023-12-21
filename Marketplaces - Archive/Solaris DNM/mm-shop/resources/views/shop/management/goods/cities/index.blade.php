{{--
This file is part of MM2-dev project.
Description: Custom places page
--}}
@extends('layouts.master')

@section('title', 'Города')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => $good->title, 'url' => url('/shop/management/goods/edit/' . $good->id)],
        ['title' => 'Города']
    ]])
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Города: {{ $good->title }}</h3>
                <hr class="small" />
                <form action="" method="post">
                    {{ csrf_field() }}
                    @foreach($cities->chunk(2) as $i => $city)
                        <div class="access-line level-2">
                            <div class="row no-padding-l">
                                <div class="access-line-checks col-xs-11 col-xs-offset-1 no-padding-l">
                                    <div class="checkbox">
                                        <label>
                                            <input name="cities[]" type="checkbox" value="{{ $city->first()->id }}" {{ ($toggles[$city->first()->id]) ? 'checked' : '' }}> {{ $city->first()->title }}
                                        </label>
                                    </div>
                                </div>
                                @if ($city->count() > 1) {{-- this condition is only for latest chunk --}}
                                <div class="col-xs-11">
                                    <div class="checkbox">
                                        <label>
                                            <input name="cities[]" type="checkbox" value="{{ $city->last()->id }}" {{ ($toggles[$city->last()->id]) ? 'checked' : '' }}> {{ $city->last()->title }}
                                        </label>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Сохранить изменения</button>
                        &nbsp;
                        <a class="text-muted" href="{{ url("/shop/management/goods/edit/".$good->id) }}">вернуться назад</a>
                    </div>
                </form>
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-places-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection