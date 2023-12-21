{{--
This file is part of MM2-dev project.
Description: Finances wallet delete page
--}}
@extends('layouts.master')

@section('title', 'Удаление кошелька :: Финансы')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.finances.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Удаление кошелька</h3>
                <hr class="small" />
                <form action="" method="post">
                    {{ csrf_field() }}
                    <p>
                        Вы действительно хотите удалить кошелек "{{ $wallet->title }}"? Данная операция необратима. <br />
                        Все средства, находящиеся на балансе, будут переведены на основной кошелек.
                    </p>
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
            @include('shop.management.components.block-finances-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection