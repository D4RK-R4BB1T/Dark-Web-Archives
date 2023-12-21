{{--
This file is part of MM2-catalog project.
Description: Shops page
--}}
@extends('layouts.master')

@section('title', __('shop.Shops'))

@section('content')
    <div class="row">
        <div class="col-xs-24 animated fadeIn">
            <h3>{{ __('shop.Shops available through the catalog') }}</h3>
            <hr class="small" />
            @include('shops.components.component-search')
            <div style="margin-bottom: 8px"></div>
            @if ($shops->count() == 0)
                <div class="alert alert-info">Не найдено ни одного магазина соответствующего заданным критериям.</div>
            @endif

            @foreach($shops->chunk(3) as $shopsChunk)
                <div class="row">
                    @foreach ($shopsChunk as $shop)
                    <div class="col-xs-24 col-sm-8">
                        <a target="_blank" href="{{ catalog_jump_url($shop->id, '/') }}" style="color: inherit !important;">
                        <div class="well block" style="margin-bottom: 0">
                            <div class="row">
                                <div class="col-xs-8 col-sm-7">
                                    <img src="{{ $shop->avatar() }}" class="img-responsive" alt="avatar" />
                                </div>
                                <div class="col-xs-16 col-sm-17">
                                    <h4 style="margin-top: 0">
                                        {{ $shop->title }}
                                    </h4>
                                    @include('layouts.components.sections-rating', ['rating' => $shop->getRating()])<br />
                                    <h5 style="margin-bottom: 0">
                                        <i class="glyphicon glyphicon-user"></i>&nbsp; {{ __('layout.Users') }}: {{ $shop->getUsersCountRange() }}
                                    </h5>
                                    <h5 style="margin-bottom: 0">
                                        <i class="glyphicon glyphicon-tags"></i>&nbsp; {{ __('layout.Orders') }}: {{ $shop->getOrdersCountRange() }}
                                    </h5>
                                    @if(Auth::user() && Auth::user()->isAdmin())
                                        <h5 style="margin-bottom: 0">
                                            <a href="/admin/view_shops?id={{ $shop->id }}">Посмотреть в админке</a>
                                        </h5>

                                        <h5 style="margin-bottom: 0">
                                            <a href="/admin/toggle_shop?id={{ $shop->id }}">@if($shop->enabled){{ __('shop.Disable') }}@else{{ __('shop.Enable') }}@endif</a>
                                        </h5>
                                    @endif
                                </div>
                            </div>
                        </div>
                        </a>
                        <div class="hidden visible-xs">&nbsp;</div>
                    </div>
                    @endforeach
                </div>
                <hr class="hidden-xs" style="margin-top: 6px; margin-bottom: 6px"/>
            @endforeach

            @if ($shops->total() > $shops->perPage())
                <div class="text-center">
                    {{ $shops->appends(request()->input())->links() }}
                </div>
                <hr class="small" />
            @endif
        </div>
    </div> <!-- /.row -->
@endsection