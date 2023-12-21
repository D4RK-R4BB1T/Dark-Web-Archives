{{--
This file is part of MM2-dev project.
Description: Main page of the shop
--}}
@extends('layouts.master')

@section('title', 'Редактирование квеста')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => "$good->title ({$position->package->city->title})", 'url' => Auth::user()->can('management-goods-edit', $good)
            ? '/shop/management/goods/packages/' . $good->id
            : NULL],
        ['title' => 'Квесты - ' . $position->package->getHumanWeight(), 'url' => url('/shop/management/goods/quests/' . $good->id . '/' . $position->package_id)],
        ['title' => 'Редактирование квеста']
    ]])
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Редактирование квеста: {{ $good->title }} ({{ $position->package->city->title }})</h3>
                <hr class="small" />
                <form action="" role="form" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="row">
                        @if (in_array($position->package->city->id, \App\City::citiesWithRegions()))
                            <div class="col-xs-8">
                                <div class="form-group condensed has-feedback {{ $errors->has('region') ? 'has-error' : '' }}">
                                    <select name="region" class="form-control" title="Округ">
                                        <option value="">Округ</option>
                                        @foreach ($position->package->city->regions as $region)
                                            <option value="{{ $region->id }}" {{ (old('region') ?: traverse($position, 'region->id')) == $region->id ? 'selected' : '' }}>{{ $region->title }}</option>
                                        @endforeach
                                    </select>
                                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                    @if ($errors->has('region'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('region') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="col-xs-8">
                            <div class="form-group condensed has-feedback {{ $errors->has('custom_place') ? 'has-error' : ''}}">
                                <select name="custom_place" class="form-control" title="Выберите место">
                                    <option value="">Выберите место</option>
                                    @foreach($good->customPlaces as $place)
                                        <option value="{{ $place->id }}" {{ (old('custom_place') ?: traverse($position, 'customPlace->id')) == $place->id ? 'selected' : '' }}>{{ $place->title }}</option>
                                    @endforeach
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                @if ($errors->has('custom_place'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('custom_place') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-xs-{{ in_array($position->package->city->id, \App\City::citiesWithRegions()) ? '8' : '16' }}">
                            <div class="form-group condensed {{ $errors->has('custom_place_title') ? 'has-error' : '' }}">
                                <input name="custom_place_title" class="form-control" value="{{ old('custom_place_title') }}" placeholder="Или создайте новое...">
                                @if ($errors->has('custom_place_title'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('custom_place_title') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <p class="text-muted">Округ и кастомное место необязательно к заполнению. При создании нового места оно привязывается к выбранному округу.</p>
                    <hr class="condensed" />
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group has-feedback{{ $errors->has('package') ? ' has-error' : '' }}">
                                <select name="package" class="form-control" title="Упаковка">
                                    <option value="">Упаковка</option>
                                    @foreach ($packages as $package)
                                        <option value="{{ $package->id }}" {{ (old('package') ?: $position->package_id) == $package->id ? 'selected' : '' }}>{{ $package->getHumanWeight() }} ({{ $package->getHumanPrice() }})</option>
                                    @endforeach
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>

                                @if ($errors->has('package'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('package') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-12 -->
                        <div class="col-xs-12">
                            <div class="form-group has-feedback{{ $errors->has('employee') ? ' has-error' : '' }}">
                                <select name="employee" class="form-control" title="Упаковка">
                                    <option value="">Сотрудник</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ (old('employee') ?: $position->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->getPrivateName() }} ({{ $employee->getRole() }})</option>
                                    @endforeach
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>

                                @if ($errors->has('employee'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('employee') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-12 -->
                    </div> <!-- /.row -->
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group {{ $errors->has('quest') ? ' has-error' : '' }}">
                                <textarea class="form-control" name="quest" rows="8" title="Описание места" placeholder="Описание места">{{ old('quest') ?: $position->quest }}</textarea>
                                @if ($errors->has('quest'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('quest') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div> <!-- /.row -->
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Отредактировать квест</button>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-quests-reminder')
        </div> <!-- /.col-sm-6 -->

    </div> <!-- /.row -->
@endsection