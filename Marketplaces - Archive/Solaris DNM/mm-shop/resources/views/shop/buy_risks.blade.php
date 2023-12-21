{{-- 
This file is part of MM2-dev project. 
Description: Buy confirmation page
--}}
@extends('layouts.master')

@section('title', 'Подтверждение заказа :: ' . $good->title)

@section('content')
    @include('layouts.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
        'breadcrumbs' =>
        [
            // BREADCRUMB_CATALOG,
            // BREADCRUMB_SHOPS,
            ['title' => $shop->title, 'url' => url('/shop/' . $shop->slug)],
            ['title' => $good->title, 'url' => url('/shop/' . $shop->slug . '/goods/' . $good->id)],
            ['title' => 'Подтверждение заказа']
        ]
    ])

    <div class="row">
        <div class="col-sm-6 col-md-6 col-lg-5">
            @include('shop.sidebar')
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-13 col-md-13 col-lg-13 animated fadeIn">
            <form role="form" action="" method="get">
                <input type="hidden" name="accepted" value="true">
                <div class="well block good-info">
                    <h3>Подтверждение заказа</h3>
                    <hr class="small" />
                    <div class="alert alert-warning">
                        Уважаемый покупатель, обращаем ваше внимание, что диспуты на данный момент разбирают сами магазины. <br />
                        В случае возникновения проблем вы будете ее решать с магазином один на один. <br />
                        Если вы хотите подстраховаться, то попросите воспользоваться гарантом нашего форума. Магазин не вправе отказать!
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Продолжить покупку</button>
                        &nbsp;
                        <a class="btn btn-success" href="{{ URL::previous() }}">Отказаться от покупки</a>
                    </div>
                </div> <!-- / .well -->
            </form>
        </div> <!-- /.col-lg-13 -->

        <div class="col-sm-5 col-md-5 col-lg-6 animated fadeIn">
            @if ($package->preorder)
                @include('shop.components.block-buy-preorder-reminder')
            @endif
        </div>
    </div> <!-- /.row -->
@endsection