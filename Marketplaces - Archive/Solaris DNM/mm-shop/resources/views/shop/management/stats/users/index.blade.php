{{--
This file is part of MM2-dev project.
Description: Shop management users stats page
--}}
@extends('layouts.master')

@section('title', 'Пользователи :: Статистика')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.stats.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            @include('shop.management.stats.components.component-users-filter')
            <div class="well block">
                <h3>Информация о пользователях</h3>
                <hr class="small" />
                <p>
                    <div class="row">
                        <div class="col-xs-14 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Пользователей в магазине:</span>
                        </div>
                        <div class="col-xs-10 col-sm-13 col-md-16">
                            {{ $usersCount }}
                        </div>
                    </div>
                </p>
                <div class="table-responsive">
                    <table class="table table-header" style="margin-bottom: 0">
                        <thead>
                        <tr>
                            <td>Пользователь</td>
                            <td>Кол-во покупок</td>
                            <td>Дата регистрации</td>
                            <td>Последний вход</td>
                            <td></td>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{!! $user->getPublicDecoratedName() !!}</td>
                                <td>
                                    @can('management-sections-orders')
                                        <a class="hint--top" aria-label="Посмотреть покупки" href="{{ url('/shop/management/orders?user='.$user->id) }}">
                                            {{ $user->buy_count }} {{ plural($user->buy_count, ['покупка', 'покупки', 'покупок']) }}
                                        </a>
                                    @else
                                        {{ $user->buy_count }} {{ plural($user->buy_count, ['покупка', 'покупки', 'покупок']) }}
                                    @endcan
                                </td>
                                <td>{{ $user->created_at->format('d.m.Y в H:i') }}</td>
                                <td>{{ ($date = $user->getLastLogin()) ? $date->format('d.m.Y в H:i') : '-' }}</td>
                                <td>
                                    @can('management-sections-messages')
                                        @if ($user->id !== \Auth::user()->id)
                                            <a href="{{ url('/shop/management/messages/new?user='.$user->id) }}" class="dark-link hint--top" aria-label="Отправить сообщение"><i class="glyphicon glyphicon-envelope"></i></a>
                                        @else
                                            <a class="text-muted hint--top" href="#" aria-label="Это вы :)"><i class="glyphicon glyphicon-envelope"></i></a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($users->total() > $users->perPage())
                    <hr class="small" />
                    <div class="text-center">
                        {{ $users->appends(request()->input())->links() }}
                    </div>
                @endif
            </div>

            @if ($shop->visitors_chart_url)
                <div class="well block">
                    <h3>Статистика посещений</h3>
                    <hr class="small" />
                    <img class="img-responsive" src="{{ url($shop->visitors_chart_url) }}" />
                    <hr class="small" />
                    <span class="text-muted">Статистика обновляется ежедневно в 00:00 по московскому времени.</span>
                </div>
            @endif
        </div> <!-- /.col-sm-18 -->
    </div> <!-- /.row -->
@endsection