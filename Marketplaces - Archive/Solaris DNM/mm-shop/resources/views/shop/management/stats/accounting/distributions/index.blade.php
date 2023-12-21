{{--
This file is part of MM2-dev project.
Description: Accounting lot add
--}}
@extends('layouts.master')

@section('title', 'Распределение товара :: Учет товаров :: Статистикв')

@section('content')
    @include('shop.management.components.sections-menu')

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.stats.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Распределение товара: {{ traverse($lot, 'good->title') ?: '-' }}</h3>
                <hr class="small" />
                @if ($distributions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>Сотрудник</td>
                                <td>Дата выдачи</td>
                                <td>Количество</td>
                                <td>Остаток</td>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($distributions as $distribution)
                                <tr>
                                    <td>@if($employee = traverse($distribution, 'employee')){{ $employee->getPrivateName() }}@else - @endif</td>
                                    <td>{{ $distribution->updated_at->format('d.m в H:i') }}</td>
                                    <td>{{ $distribution->getHumanTotalWeight() }}</td>
                                    <td>{{ $distribution->getHumanAvailableWeight() }}</td>
                                    <td class="text-right">
                                        <a class="dark-link hint--left" aria-label="Редактировать" href="{{ url('/shop/management/stats/accounting/distribution/edit/'.$lot->id.'/'.$distribution->id) }}"><i class="glyphicon glyphicon-edit"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info" style="margin-bottom: 0">Товар еще не выдавался сотрудникам.</div>
                @endif
                <hr class="small" />
                <div class="text-center">
                    <a class="btn btn-orange" href="{{ url('/shop/management/stats/accounting/distribution/'.$lot->id) }}">Выдать товар сотруднику</a>
                    &nbsp;
                    <a class="text-muted" href="{{ URL::previous() }}">вернуться назад</a>
                </div>
            </div>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-stats-accounting-lot-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection