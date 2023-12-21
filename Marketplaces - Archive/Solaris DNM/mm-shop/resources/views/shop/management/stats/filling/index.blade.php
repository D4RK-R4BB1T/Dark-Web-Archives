{{--
This file is part of MM2-dev project.
Description: Shop management orders stats page
--}}
@extends('layouts.master')

@section('title', 'Заполненность магазина :: Статистика')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.stats.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            <div class="well block">
                <h3>Общая заполненность магазина</h3>
                <hr class="small" />
                <p>
                <div class="row">
                    <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                        <span class="text-muted">Количество товаров:</span>
                    </div>
                    <div class="col-xs-10 col-sm-13 col-md-16">
                        {{ $totalStats['goods_count'] }}
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                        <span class="text-muted">Количество доступных квестов:</span>
                    </div>
                    <div class="col-xs-10 col-sm-13 col-md-16">
                        {{ $totalStats['quests_count'] }}
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                        <span class="text-muted">Стоимость товаров:</span>
                    </div>
                    <div class="col-xs-10 col-sm-13 col-md-16">
                        {{ $totalStats['quests_total'] }}
                    </div>
                </div>
                </p>
            </div>

            <div class="well block">
                <h3>Заполненность магазина</h3>
                <hr class="small" />
                @if ($stats->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>Товар</td>
                                <td>Упаковки</td>
                                <td>Квесты</td>
                                <td>Общая стоимость</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($stats as $stat)
                                <tr class="{{ $stat['quests_count'] === 0 ? 'bg-danger' : '' }}">
                                    <td>
                                        <a href="{{ url('/shop/'.$shop->slug.'/goods/'.$stat['id']) }}">{{ $stat['title'] }}</a><br />
                                        <i class="glyphicon glyphicon-map-marker"></i> {{ $stat['city'] }}
                                    </td>
                                    <td>
                                        <span class="dashed hint--top" aria-label="{{ $stat['packages'] }}">
                                            {{ $stat['packages_count'] }} {{ plural($stat['packages_count'], ['упаковка', 'упаковки', 'упаковок']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="dashed hint--top" aria-label="{{ $stat['quests'] }}">
                                            {{ $stat['quests_count'] }} {{ plural($stat['quests_count'], ['квест', 'квеста', 'квестов']) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $stat['quests_total'] ?: '-' }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">Не найдено ни одного товара.</div>
                @endif
            </div>
        </div> <!-- /.col-sm-18 -->
    </div> <!-- /.row -->
@endsection