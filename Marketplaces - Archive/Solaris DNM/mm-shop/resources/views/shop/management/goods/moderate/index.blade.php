{{--
This file is part of MM2-dev project.
Description: модерация готовых квестов
--}}
@extends('layouts.master')

@section('title', 'Модерация квестов')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => 'Модерация квестов']
    ]])

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
            @include('shop.management.components.block-moderate-filter')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Квесты, нуждающиеся в проверке и подтверждении</h3>
                <hr class="small" />

                @if(count($positions) > 0)
                    <form action="" method="POST">
                    {{ csrf_field() }}
                    <table class="table table-header table-minimal">
                        <thead>
                            <tr>
                                <td></td>
                                <td>Добавлен</td>
                                <td>Товар</td>
                                <td>Вес</td>
                                <td>Работник</td>
                                <td>Город</td>
                                <td>Район</td>
                                <td class="col-xs-4"></td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($positions as $position)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="positions[]" value="{{ $position->id }}">
                                    </td>
                                    <td>{{ $position->created_at->format('d.m.Y в H:i') }}</td>
                                    <td>{{ $position->good_title }}</td>
                                    <td>{{ $position->package->getHumanWeight() }}</td>
                                    <td>{{ $position->username }}</td>
                                    <td>{{ $position->package->city->title }}</td>
                                    <td>
                                        @if($position->region)
                                            {{ $position->region->title }}
                                        @elseif($position->customPlace)
                                            {{ ($region = traverse($position, 'customPlace->region->title')) ? $region . ' /' : '' }} {{ $position->customPlace->title }}
                                        @endif
                                    </td>
                                    <td style="font-size: 15px" class="text-right">
                                        <a href="{{ url('/shop/management/goods/quests/edit/' . $position->good_id.'/'.$position->id) }}" class="dark-link hint--top" aria-label="Просмотр квеста">
                                            <i class="glyphicon glyphicon-eye-open"></i>
                                        </a>
                                        &nbsp;
                                        <a href="{{ url('/shop/management/goods/moderation/accept/' . $position->id) }}?_token={{ csrf_token() }}" class="dark-link hint--top" aria-label="Отправить на витрину">
                                            <i class="glyphicon glyphicon-saved"></i>
                                        </a>
                                        &nbsp;
                                        <a href="{{ url('/shop/management/goods/moderation/decline/' . $position->id) }}?_token={{ csrf_token() }}" class="text-danger hint--top hint--error" aria-label="Удалить квест">
                                            <i class="glyphicon glyphicon-ban-circle"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <hr class="small" />
                    <div>
                        <button type="submit" name="accept" class="btn btn-orange" value="1">Принять квесты</button>
                        <button type="submit" name="decline" class="btn btn-default" value="1">Удалить квесты</button>
                    </div>
                    </form>

                    @if ($positions->total() > $positions->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $positions->appends([
                                'username' => request()->get('username'),
                                'city_id' => request()->get('city_id'),
                                'region_id' => request()->get('region_id')
                               ])->links() }}
                        </div>
                    @endif
                @else
                    <div class="alert alert-info" style="margin-bottom: 0">Квестов для проверки нет.</div>
                @endif
            </div>
        </div>
    </div>
@endsection