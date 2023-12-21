{{--
This file is part of MM2-dev project.
Description: Main page of the shop
--}}
@extends('layouts.master')

@section('title', 'Настройка упаковок')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => "{$good->title} ($city->title)", 'url' => url('/shop/management/goods/packages/' . $good->id)],
        ['title' => 'Упаковки']
    ]])
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            <div class="well block">
                <h3>Упаковки товара: {{ $good->title }} ({{ $city->title }})</h3>
                <hr class="small"/>
                @if(count($packages) > 0)
                    <div class="table-responsive">
                    <table class="table table-header" style="margin-bottom: 0">
                        <thead>
                        <tr>
                            <td class="">Упаковка</td>
                            <td class="">Стоимость</td>
                            <td class="">Наличие</td>
                            <td class="">Выплаты работнику</td>
                            <td class="">Штраф работнику</td>
                            <td></td>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($packages as $package)
                            <tr class="{{
                                $package->id == request()->query('highlight')
                                    ? 'bg-warning'
                                    : (($package->preorder || $package->getAvailablePositionsCount() > 0) ? 'bg-success' : 'bg-danger')
                                    }}">
                                <td>{{ $package->getHumanWeight() }}</td>
                                <td>{{ $package->getHumanPrice() }}</td>
                                <td class="">
                                    {{ $package->getAvailablePositionsCount() }}
                                    @if (!$package->preorder && $package->getNotModeratedPositionsCount() > 0)
                                        @if (Auth::user()->can('management-sections-moderate'))
                                            <a class="dark-link dashed hint--top" aria-label="{{ $package->getNotModeratedPositionsCount() }} в модерации, нажмите для просмотра" href="{{ url('/shop/management/goods/moderation') }}">
                                                (+{{ $package->getNotModeratedPositionsCount() }})
                                            </a>
                                        @else
                                            <a class="dark-link dashed hint--top" aria-label="{{ $package->getNotModeratedPositionsCount() }} в модерации" href="#">
                                                (+{{ $package->getNotModeratedPositionsCount() }})
                                            </a>
                                        @endif
                                    @endif
                                </td>
                                <td class="">{{ $package->employee_reward ? human_price($package->employee_reward, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) : '-' }}</td>
                                <td class="">{{ $package->employee_penalty ? human_price($package->employee_penalty, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) : '-' }}</td>
                                <td class="text-right" style="font-size: 15px">
                                    @if (!$package->preorder)
                                        @can('management-quests-create', [$good, $city])
                                            <a class="dark-link hint--top" aria-label="Квесты" href="{{ url('/shop/management/goods/quests/'.$good->id.'/'.$package->id) }}"><i class="glyphicon glyphicon-map-marker"></i></a>
                                            &nbsp;
                                        @endcan
                                    @endif
                                    @can('management-goods-edit', $good)
                                        <a class="dark-link hint--top" aria-label="Редактировать" href="{{ url('/shop/management/goods/packages/edit/'.$good->id.'/'.$package->id) }}"><i class="glyphicon glyphicon-edit"></i></a>
                                    @endcan
                                    @can('management-goods-delete', $good)
                                        &nbsp;<a class="text-danger hint--top hint--error" aria-label="Удалить" href="{{ url('/shop/management/goods/packages/delete/'.$good->id.'/'.$package->id) }}"><i class="glyphicon glyphicon-remove"></i></a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                @else
                    <div class="alert alert-info">У данного товара еще нет упаковок.</div>
                @endif
                @can('management-goods-create', $good)
                    <hr class="small" />
                    <div class="text-center">
                        <a class="btn btn-orange" href="{{ url('/shop/management/goods/packages/add/'.$good->id.'/'.$city->id) }}">Добавить упаковку</a>
                        &nbsp;
                        <a class="text-muted" href="{{ url('/shop/management/goods/places/'.$good->id.'/'.$city->id) }}"><i class="glyphicon glyphicon-map-marker"></i> Кастомные места</a>
                    </div>
                @endcan
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->

        {{--<div class="col-sm-6 animated fadeIn">--}}
            {{--@include('shop.management.components.block-goods-packages-reminder')--}}
        {{--</div> <!-- /.col-sm-6 -->--}}
    </div> <!-- /.row -->


@endsection