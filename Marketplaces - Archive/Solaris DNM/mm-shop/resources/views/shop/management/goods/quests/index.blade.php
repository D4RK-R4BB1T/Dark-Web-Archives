{{--
This file is part of MM2-dev project.
Description: Quests list page
--}}
@extends('layouts.master')

@section('title', 'Квесты')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => "{$good->title} ({$package->city->title})", 'url' => url('/shop/management/goods/packages/' . $good->id)],
        ['title' => 'Упаковки', 'url' => url('/shop/management/goods/packages/city/' . $good->id . '/' . $package->city->id)],
        ['title' => 'Квесты - ' . $package->getHumanWeight()]
    ]])

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Квесты: {{ $good->title }} ({{ $package->city->title }}) <span class="pull-right">{{ $package->getHumanWeight() }}</span></h3>
                <hr class="small" />
                @if(count($positions) > 0)
                    <table class="table table-header table-minimal">
                        <thead>
                        <tr>
                            <td>Добавлен</td>
                            <td>Работник</td>
                            <td>Место</td>
                            <td>Текст</td>
                            <td class="col-xs-4"></td>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($positions as $position)
                            <tr>
                                <td>{{ $position->created_at->format('d.m.Y в H:i') }}</td>
{{--                                <td>{{ traverse($position, 'employee->getPrivateName()') ?: '-' }}</td>--}}
                                <td>
                                    @if($employee = traverse($position, 'employee'))
                                        {{ $employee->getPrivateName() }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if (!$position->region && !$position->customPlace)
                                        {{ $package->city->title }}
                                    @else
                                        @if ($position->region)
                                            {{ $position->region->title }}
                                        @elseif($position->customPlace)
                                            {{ ($region = traverse($position, 'customPlace->region->title')) ? $region . ' /' : '' }} {{ $position->customPlace->title }}
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    {{ str_limit($position->quest, 50) }}
                                </td>
                                <td class="text-right" style="font-size: 15px">
                                    <a href="{{ url('/shop/management/goods/quests/view/'.$good->id.'/'.$position->id) }}" class="dark-link hint--top" aria-label="Посмотреть"><i class="glyphicon glyphicon-eye-open"></i> </a>
                                    @can('management-quests-edit', [$good, $package->city])
                                    &nbsp;
                                    <a href="{{ url('/shop/management/goods/quests/edit/'.$good->id.'/'.$position->id) }}" class="dark-link hint--top" aria-label="Редактировать"><i class="glyphicon glyphicon-pencil"></i> </a>
                                    @endcan
                                    @can('management-quests-delete', [$good, $package->city])
                                    &nbsp;
                                    <a href="{{ url('/shop/management/goods/quests/delete/'.$good->id.'/'.$position->id) }}" class="text-danger hint--top hint--error" aria-label="Удалить"><i class="glyphicon glyphicon-remove"></i> </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-info" style="margin-bottom: 0">У данного товара еще нет квестов.</div>
                @endif
                <hr class="small" />
                <div class="text-center">
                    <a class="btn btn-orange" href="{{ url('/shop/management/goods/quests/add/'.$good->id.'/'.$package->city->id.'?package='.$package->id) }}">Добавить квест</a>
                    &nbsp;
                    <a class="text-muted" href="{{ url('/shop/management/goods/packages/'.$good->id) }}">вернуться назад</a>
                </div>
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->
    </div> <!-- /.row -->


@endsection
