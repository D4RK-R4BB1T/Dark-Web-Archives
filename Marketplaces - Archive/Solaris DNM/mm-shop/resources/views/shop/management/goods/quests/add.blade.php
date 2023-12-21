{{--
This file is part of MM2-dev project.
Description: Main page of the shop
--}}
@extends('layouts.master')

@section('title', 'Добавление квеста')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => "{$good->title} ({$city->title})", 'url' =>
            (Auth::user()->can('management-goods-edit', $good) || Auth::user()->can('management-quests-create', [$good, $city]))
            ? '/shop/management/goods/packages/' . $good->id
            : NULL],
        ['title' => 'Добавление квеста']
    ]])

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <form action="" role="form" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                @for ($i = 0; $i < $questsCount; $i++)
                    <div class="well block">
                        <h3>Добавление квеста: {{ $good->title }} ({{ $city->title }})</h3>
                        <hr class="small" />
                        <div class="row">
                            @if (in_array($city->id, \App\City::citiesWithRegions()))
                                <div class="col-xs-8">
                                    <div class="form-group condensed has-feedback {{ $errors->has("quests.$i.region") ? 'has-error' : '' }}">
                                        <select name="quests[{{ $i }}][region]" class="form-control" title="Округ">
                                            <option value="">Округ</option>
                                            @foreach ($city->regions as $region)
                                                <option value="{{ $region->id }}" {{ old("quests.$i.region") == $region->id ? 'selected' : '' }}>{{ $region->title }}</option>
                                            @endforeach
                                        </select>
                                        <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                        @if ($errors->has("quests.$i.region"))
                                            <span class="help-block">
                                                <strong>{{ $errors->first("quests.$i.region") }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="col-xs-8">
                                <div class="form-group condensed has-feedback {{ $errors->has("quests.$i.custom_place") ? 'has-error' : ''}}">
                                    <select name="quests[{{ $i }}][custom_place]" class="form-control" title="Выберите место">
                                        <option value="">Выберите место</option>
                                        @foreach($good->customPlaces as $place)
                                            <option value="{{ $place->id }}" {{ old("quests.$i.custom_place") == $place->id ? 'selected' : '' }}>{{ $place->title }}</option>
                                        @endforeach
                                    </select>
                                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                    @if ($errors->has("quests.$i.custom_place"))
                                        <span class="help-block">
                                            <strong>{{ $errors->first("quests.$i.custom_place") }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-xs-{{ in_array($city->id, \App\City::citiesWithRegions()) ? '8' : '16' }}">
                                <div class="form-group condensed {{ $errors->has("quests.$i.custom_place_title") ? 'has-error' : '' }}">
                                    <input name="quests[{{ $i }}][custom_place_title]" class="form-control" value="{{ old("quests.$i.custom_place_title") }}" placeholder="Или создайте новое...">
                                    @if ($errors->has("quests.$i.custom_place_title"))
                                        <span class="help-block">
                                            <strong>{{ $errors->first("quests.$i.custom_place_title") }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <p class="text-muted">Округ и кастомное место необязательно к заполнению. При создании нового места оно привязывается к выбранному округу.</p>
                        <hr class="condensed" />
                        <div class="row">
                            <div class="col-xs-{{ Gate::allows('management-owner') ? '12' : '24' }}">
                                <div class="form-group has-feedback{{ $errors->has("quests.$i.package") ? ' has-error' : '' }}">
                                    <select name="quests[{{ $i }}][package]" class="form-control" title="Упаковка">
                                        <option value="">Упаковка</option>
                                        @foreach ($packages as $package)
                                            <option value="{{ $package->id }}" {{ old("quests.$i.package") == $package->id ? 'selected' : (request()->query('package') == $package->id ? 'selected' : '') }}>{{ $package->getHumanWeight() }} ({{ $package->getHumanPrice() }})</option>
                                        @endforeach
                                    </select>
                                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>

                                    @if ($errors->has("quests.$i.package"))
                                        <span class="help-block">
                                            <strong>{{ $errors->first("quests.$i.package") }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div> <!-- /.col-xs-12 -->
                            @can('management-owner')
                                <div class="col-xs-12">
                                    <div class="form-group has-feedback{{ $errors->has("quests.$i.employee") ? ' has-error' : '' }}">
                                        <select name="quests[{{ $i }}][employee]" class="form-control" title="Сотрудник">
                                            <option value="">Сотрудник</option>
                                            @foreach ($employees as $employee)
                                                <option value="{{ $employee->id }}" {{ old("quests.$i.employee") == $employee->id ? 'selected' : '' }}>{{ $employee->getPrivateName() }} ({{ $employee->getRole() }})</option>
                                            @endforeach
                                        </select>
                                        <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>

                                        @if ($errors->has("quests.$i.employee"))
                                            <span class="help-block">
                                                <strong>{{ $errors->first("quests.$i.employee") }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div> <!-- /.col-xs-12 -->
                            @endcan
                        </div> <!-- /.row -->
                        <div class="row">
                            <div class="col-xs-24">
                                <div class="form-group {{ $errors->has("quests.$i.quest") ? ' has-error' : '' }}">
                                    <textarea class="form-control" name="quests[{{ $i }}][quest]" rows="8" title="Описание места" placeholder="Описание места">{{ old("quests.$i.quest") }}</textarea>
                                    @if ($errors->has("quests.$i.quest"))
                                        <span class="help-block">
                                            <strong>{{ $errors->first("quests.$i.quest") }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div> <!-- /.row -->
                        @if ($i === $questsCount - 1)
                            <hr class="small" />
                            <div class="text-center">
                                <button type="submit" class="btn btn-orange">Добавить квест</button>
                            </div>
                        @endif
                    </div>
                @endfor
            </form>

            <div class="well block">
                <h3>Мульти-добавление квестов</h3>
                <hr class="small" />
                <form role="form" action="" method="get">
                    <input type="hidden" name="package" value="{{ request()->query('package') }}" />
                    <div class="form-group">
                        <span class="control-label">Введите необходимое количество квестов (не более 10):</span>
                        <input type="text" class="form-control" name="count" value="{{ $questsCount }}" />
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Применить</button>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-quests-reminder')
        </div> <!-- /.col-sm-6 -->

    </div> <!-- /.row -->
@endsection