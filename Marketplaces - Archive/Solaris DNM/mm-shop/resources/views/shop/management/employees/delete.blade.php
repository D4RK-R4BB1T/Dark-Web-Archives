{{--
This file is part of MM2-dev project.
Description: Employees delete page
--}}
@extends('layouts.master')

@section('title', 'Увольнение сотрудника :: Сотрудники')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.employees.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Увольнение сотрудника</h3>
                <hr class="small" />
                <form action="" method="post">
                    {{ csrf_field() }}
                    <p>Вы действительно хотите уволить данного сотрудника? Данная операция необратима.</p>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Подтвердить</button>
                        &nbsp;
                        <a class="text-muted" href="{{ URL::previous() }}">вернуться назад</a>
                    </div>
                </form>
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-packages-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection