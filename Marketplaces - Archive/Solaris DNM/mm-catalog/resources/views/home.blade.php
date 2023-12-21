@extends('layouts.master')

@section('title', 'Главная')

@section('header_scripts')
    <style>
        .row.grid { margin: 0; }
        .spacer { margin-top: 15px; }
        .row.grid div {
            margin-left: -1px;
            margin-top: -1px;
            border: 1px solid #e5e5e5;
            padding: 20px;
        }
        .row.grid h4 {
            color: #4E5254;
            height: 17px;
            overflow: hidden;
        }
        .row.grid .img-responsive {
            max-width: 100%;
        }
    </style>
@endsection

@section('content')
    <?php $cities = \App\City::allCached(); ?>
    <form action="/catalog">
        <div class="row">
            <div class="col-xs-24 col-sm-9">
                <div class="form-group has-feedback">
                    <input class="form-control" name="query" placeholder="{{ __('layout.Search term') }}" {{ autofocus_on_desktop() }} />
                    <span class="glyphicon glyphicon-search form-control-feedback"></span>
                </div>
            </div>
            <div class="col-xs-24 col-sm-6">
                <div class="form-group has-feedback">
                    <select name="city" class="form-control" title="{{ __('layout.Choose city') }}">
                        <option value="">{{ __('layout.Any city') }}</option>
                        @foreach($cities as $city)
                            <option value="{{ $city['id'] }}">{{ $city['title'] }}</option>
                        @endforeach
                    </select>
                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                </div>
            </div>
            <div class="col-xs-24 col-sm-6">
                <div class="form-group transparent has-feedback">
                    <select name="availability" class="form-control" title="{{ __('layout.Select type of quest') }}">
                        <option value="all">{{ __('layout.All') }}</option>
                        <option value="ready">{{ __('layout.Ready only') }}</option>
                    </select>
                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                </div>
            </div>
            <div class="col-xs-24 col-sm-3">
                <div class="form-group" style="height: 33px; line-height: 32px; width: 100%">
                    <button style="width: 100%" class="btn btn-orange" type="submit">{{ __('layout.Search') }}</button>
                </div>
            </div>
        </div>
    </form>
    <div class="spacer"></div>
    @foreach($shops->chunk(4) as $shopsChunk)
        <div class="row grid">
            @foreach ($shopsChunk as $shop)
                <a target="_blank" role="button" href="{{ catalog_jump_url($shop->id, '/') }}">
                    <div class="col-xs-12 col-sm-6 text-center">
                        <img src="{{ $shop->avatar() }}" class="img-responsive"><br />
                        <h4>{{ $shop->title }}</h4>

                        @include('layouts.components.sections-rating', ['rating' => $shop->getRating()])
                    </div>
                </a>
            @endforeach
        </div>
    @endforeach
    <div class="spacer"></div>
@endsection