{{--
This file is part of MM2-dev project.
Description: Quests map page
--}}
@extends('layouts.master')

@section('title', 'Карта кладов')

@section('content')
    @include('shop.management.components.sections-menu')

    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => 'Карта кладов']
    ]])

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div>

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Карта кладов</h3>
                <hr class="small" />
                @if ($skipped > 0)
                    <div class="text-center">Количество пропущенных кладов из-за отсутствия координат в описании: {{ $skipped }} из {{ $count }}</div>
                @endif
                <div id="map" style="width: 700px; height: 500px; max-width: 100%; max-height: 90vh; margin: auto"></div>
            </div>
        </div>

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-quests-map-reminder')
        </div>
    </div>

    <script>
        window._base_url = '{{ url("/") }}';
        window._map_data = JSON.parse(decodeURIComponent('{!! $data !!}'));
    </script>
    <link href="{{ url('/') . mix('assets/css/quests_map.css') }}" rel="stylesheet">

    {{-- Хаки для работы с гейтами --}}
    <style>
        .leaflet-control-layers-toggle {
            background-image: url({{ url('/') }}/assets/img/vendor/leaflet/dist/layers.png);
        }
        .leaflet-retina .leaflet-control-layers-toggle {
            background-image: url({{ url('/') }}/assets/img/vendor/leaflet/dist/layers-2x.png);
        }
        .leaflet-default-icon-path {
            background-image: url({{ url('/') }}/assets/img/vendor/leaflet/dist/marker-icon.png)
        }
        .leaflet-default-icon-icon {
            background-image: url({{ url('/') }}/assets/img/vendor/leaflet/dist/marker-icon.png), url({{ url('/') }}/assets/img/vendor/leaflet/dist/marker-icon-2x.png);
            cursor: url({{ url('/') }}/assets/img/vendor/leaflet/dist/marker-icon.png), url({{ url('/') }}/assets/img/vendor/leaflet/dist/marker-icon-2x.png), auto;
        }
        .leaflet-default-icon-shadow {
            background-image: url({{ url('/') }}/assets/img/vendor/leaflet/dist/marker-shadow.png);
            cursor: url({{ url('/') }}/assets/img/vendor/leaflet/dist/marker-shadow.png), auto;
        }
    </style>
    <script src="{{ url('/') . mix('assets/js/quests_map.js') }}"></script>
@endsection