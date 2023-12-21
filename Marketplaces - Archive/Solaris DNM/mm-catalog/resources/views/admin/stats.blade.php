@extends('layouts.master')

@section('title', 'Статистика')

@section('content')
    <div class="row">
        <div class="col-sm-7 col-md-5 col-lg-5">
            @include('admin.sidebar')
        </div>

        <div class="col-sm-17 col-md-19 col-lg-19 animated fadeIn">
            <div class="well block">
                <h3>Уникальные пользователи (авторизация)</h3>
                <table class="chart charts-css line show-labels show-primary-axis show-4-secondary-axes show-data-axes">
                    <tbody>
                    @foreach ($usersGraph as $data)
                        <tr style="z-index: {{ $loop->remaining }}">
                            <th>{{ $data['date']->format('d.m') }}</th>
                            <td style="--start: {{ $data['start'] }}; --size: {{ $data['size'] }}"> <span class="data">{{ $data['value'] }}</span> </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="well block">
                <h3>Количество заказов (только от пользователей каталога)</h3>
                <table class="chart charts-css line show-labels show-primary-axis show-4-secondary-axes show-data-axes" style="--color: orange">
                    <tbody>
                    @foreach ($ordersGraph as $data)
                        <tr style="z-index: {{ $loop->remaining }}">
                            <th>{{ $data['date']->format('d.m') }}</th>
                            <td style="--start: {{ $data['start'] }}; --size: {{ $data['size'] }}"> <span class="data">{{ $data['value'] }}</span> </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('header_scripts')
    <link href="{{ asset('/assets/css/admin.css') }}" rel="stylesheet">
    <link href="{{ asset('/assets/css/charts.min.css') }}" rel="stylesheet">
    <style>
        .chart {
            height: 200px;
            margin: 0 auto;
        }
        .data {
            z-index: 9999;
            border: 1px solid #e5e5e5;
            background-color: #f1f1f1;
        }
    </style>
@endsection