<?php
if (!isset($good)) {
    throw new \Exception(__('goods.Goods is not set'));
}

if (isset($shop)) {
    $good->shop = $shop;
}

$goodUrl = '/goods/' . $good->app_good_id;
if (request()->has('city')) {
    $goodUrl .= '?city=' . request()->get('city');
}
?>
<!-- layouts/components/component-card -->
<div class="col-xs-12 col-sm-6 card">
    <div class="thumbnail thumbnail-cover caption-slide">
        <div class="slide">
            <a target="_blank" href="{{ catalog_jump_url($good->shop->id, $goodUrl) }}">
                <img src="{{ $good->image_url_local }}" alt="{{ $good->title }}" title="{{ $good->title }}" class="img-responsive">
            </a>
            {{--<div class="caption animated fadeIn">--}}
            {{--<strong>Клады в: </strong> <br />--}}
            {{--test test--}}
            {{--</div>--}}
        </div>
        <div class="description">
            <h4><a target="_blank" class="dark-link" href="{{ catalog_jump_url($good->shop->id, $goodUrl) }}">{{ $good->title }}</a></h4>
            @if ($good->buy_count && $good->buy_count > 50)
                <div class="row">
                    <div class="col-xs-24" style="min-height: 22px; max-height: 22px; overflow: hidden">
                        @include('layouts.components.sections-rating', ['rating' => $good->rating]) <span class="text-muted">({{ $good->getHumanRating() }}, {{ $good->getBuyCountRange() }})</span>
                    </div>
                </div>
            @endif
            <div class="row">
                <div class="col-xs-2 col-sm-4 col-md-3 col-lg-2"><i class="glyphicon glyphicon-user"></i></div>
                <div class="col-xs-22 col-sm-20 col-md-21 col-lg-22" style="min-height: 19px;">
                    <h5 style="margin-top: 2px; margin-bottom: 0"><a target="_blank" href="{{ catalog_jump_url($good->shop->id, '') }}">{{ $good->shop->title }}</a></h5>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-2 col-sm-4 col-md-3 col-lg-2"><i class="glyphicon glyphicon-map-marker"></i></div>
                <div class="col-xs-22 col-sm-20 col-md-21 col-lg-22" style="min-height: 19px; max-height: 90px; overflow: hidden; mask-image: linear-gradient(180deg, #000 60%, transparent); -webkit-mask-image: linear-gradient(180deg, #000 60%, transparent);">
                    @if ($good->cities->count() > 0)
                        @foreach ($good->cities as $city)
                            <a style="font-weight: 500" href="{{ catalog_jump_url($good->shop->id, '/goods/' . $good->app_good_id . '?city=' . $city->id) }}">{{ $city->title }}</a>@if (!$loop->last) &bull; @endif
                        @endforeach
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="row">
                <div class="col-xs-2 col-sm-4 col-md-3 col-lg-2"><i class="glyphicon glyphicon-tags"></i></div>
                <div class="col-xs-22 col-sm-20 col-md-21 col-lg-22" style="min-height: 19px;">
                    <h5 style="margin-top: 2px; margin-bottom: 0"><a href="/catalog?category={{ $good->category()->id }}">{{ $good->category()->title }}</a></h5>
                </div>
            </div>
        </div>
        @if($good && $good->getCheapestAvailablePackage() && $good->getMostExpensiveAvailablePackage())
            @if ($good->getCheapestAvailablePackage()->id === $good->getMostExpensiveAvailablePackage()->id)
                <p class="positions text-center">{{ $good->getCheapestAvailablePackage()->getHumanWeight() }}</p>
                <a target="_blank" href="{{ catalog_jump_url($good->shop->id, $goodUrl) }}" class="btn btn-orange">{{ $good->getCheapestAvailablePackage()->getHumanPrice() }}</a>
            @else
                <p class="positions text-center">от {{ $good->getCheapestAvailablePackage()->getHumanWeight() }} до {{ $good->getMostExpensiveAvailablePackage()->getHumanWeight() }}</p>
                <a target="_blank" href="{{ catalog_jump_url($good->shop->id, $goodUrl) }}" class="btn btn-orange">от {{ $good->getCheapestAvailablePackage()->getHumanPrice() }} до {{ $good->getMostExpensiveAvailablePackage()->getHumanPrice() }}</a>
            @endif
        @else
            <p class="positions text-center">от - до -</p>
            <a href="#" class="btn btn-orange">от - до -</a>
        @endif
    </div> <!-- /.thumbnail -->
</div> <!-- /.card -->
<!-- /layouts/components/component-card -->
