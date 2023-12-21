@extends('layouts.master')

@section('title', 'Удаление группы :: Скидки')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_DISCOUNTS,
        ['title' => 'Скидочные группы', 'url' => url('/shop/management/discounts/groups')],
        ['title' => 'Удаление группы']
    ]])

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.discounts.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <form action="" method="post">
                {{ csrf_field() }}
                <div class="well block good-info">
                    <h3>Удаление группы</h3>
                    <hr class="small" />
                    <p>Вы действительно хотите удалить группу <strong>{{ $group->title }}</strong>? Пользователи, находящиеся в этой группе, останутся без группы. Данная операция необратима.</p>
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
            @include('shop.management.components.block-discounts-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->


@endsection