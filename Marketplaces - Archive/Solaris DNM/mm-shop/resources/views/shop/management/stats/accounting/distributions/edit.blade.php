{{--
This file is part of MM2-dev project.
Description: Accounting distribution edit
--}}
@extends('layouts.master')

@section('title', 'Редактирование выданного товара :: Учет товаров :: Статистикв')

@section('content')
    @include('shop.management.components.sections-menu')

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.stats.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Редактирование выданного товара: {{ traverse($lot, 'good->title') ?: '-' }}</h3>
                <hr class="small" />
                <form action="" role="form" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group has-feedback{{ $errors->has("employee") ? ' has-error' : '' }}">
                                <select name="employee" class="form-control" title="Сотрудник" disabled>
                                    <option value="{{ $distribution->employee_id }}" selected>@if($employee = traverse($distribution, 'employee')){{ $employee->getPrivateName() }} ({{ traverse($distribution, 'employee->getRole()') ?: '-' }})@else - @endif</option>
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                @if ($errors->has("employee"))
                                    <span class="help-block">
                                        <strong>{{ $errors->first("employee") }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-24 -->
                    </div>
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group{{ $errors->has('amount') ? ' has-error' : '' }}">
                                <div class="input-group">
                                    <input id="amount" type="text" class="form-control" name="amount" placeholder="Количество" value="{{ (old('amount') ?: $distribution->amount) }}" required>
                                    <span class="input-group-addon">{{ \App\Packages\Utils\Formatters::getHumanMeasure($lot->measure) }}</span>
                                </div>
                                @if ($errors->has('amount'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('amount') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Минимальное значение для поля: <strong>{{ \App\Packages\Utils\Formatters::getHumanWeight($distribution->getTotalWeight() - $distribution->getAvailableWeight(), $lot->measure) }}</strong><br />
                                        Максимальное значение для поля: <strong>{{ \App\Packages\Utils\Formatters::getHumanWeight($distribution->getTotalWeight() + $lot->getUnusedWeight(), $lot->measure) }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-12 -->
                    </div> <!-- /.row -->
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group {{ $errors->has("note") ? ' has-error' : '' }}">
                                <textarea class="form-control" name="note" rows="3" title="Заметка" placeholder="Заметка">{{ old("note") ?: $distribution->note }}</textarea>
                                @if ($errors->has("note"))
                                    <span class="help-block">
                                        <strong>{{ $errors->first("note") }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Отредактировать выданный товар</button>
                        &nbsp;
                        <a class="text-muted" href="{{ URL::previous() }}">вернуться назад</a>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-stats-accounting-lot-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection