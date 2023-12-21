{{--
This file is part of MM2-dev project.
Description: Good clone page
--}}
@extends('layouts.master')

@section('title', 'Клонирование товара')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <form action="" method="post">
                {{ csrf_field() }}
                <div class="well block good-info">
                    <h3>Клонирование товара</h3>
                    <hr class="small" />
                    <p>
                        Вы точно хотите создать полную копию товара? <br />
                        Важно! <strong>Квесты</strong> и <strong>права доступа</strong> со старого товара не будут скопированы в новый.
                    </p>
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
            @include('shop.management.components.block-goods-add-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection