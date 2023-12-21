<?php
if (!isset($good)) {
    throw new \Exception('Good is not set.');
}

if (isset($shop)) {
    $good->shop = $shop;
}

$goodUrl = "/shop/{$good->shop->slug}/goods/{$good->id}";
if (request()->has('city')) {
    $goodUrl .= '?city=' . request()->get('city');
}
?>
<!-- layouts/components/component-card -->
<div class="col-xs-12 col-sm-6 card">
    <div class="thumbnail thumbnail-cover caption-slide">
        <div class="slide">
            <a href="{{ url($goodUrl) }}">
                <img src="{{ url($good->image_url) }}" alt="{{ $good->title }}" title="{{ $good->title }}" class="img-responsive">
            </a>
            {{--<div class="caption animated fadeIn">--}}
            {{--<strong>Клады в: </strong> <br />--}}
            {{--test test--}}
            {{--</div>--}}
        </div>
        <div class="description">
            <h4><a class="dark-link" href="{{ url($goodUrl) }}">{{ $good->title }}</a></h4>
            {{--<h5><i class="glyphicon glyphicon-user"></i>&nbsp;&nbsp;<a href="{{ url('/shop/'.$good->shop->slug) }}">{{ $good->shop->title }}</a></h5>--}}
            <div class="row">
                <div class="col-xs-2 col-sm-4 col-md-3 col-lg-2"><i class="glyphicon glyphicon-map-marker"></i></div>
                <div class="col-xs-22 col-sm-20 col-md-21 col-lg-22" style="max-height: 90px; overflow: hidden; mask-image: linear-gradient(180deg, #000 60%, transparent); -webkit-mask-image: linear-gradient(180deg, #000 60%, transparent);">
                    @if ($good->cities->count() > 0)
                        @foreach ($good->cities as $city)
                            <a style="font-weight: 500" href="{{ url($goodUrl.'?city='.$city->id) }}">{{ $city->title }}</a>@if (!$loop->last) &bull; @endif
                        @endforeach
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="row" style="margin-top: 3px">
                <div class="col-xs-2 col-sm-4 col-md-3 col-lg-2"><i class="glyphicon glyphicon-tags"></i></div>
                <div class="col-xs-22 col-sm-20 col-md-21 col-lg-22">
                    <h5 class="category"><a href="?category={{ $good->category()->id }}">{{ $good->category()->title }}</a></h5>
                </div>
            </div>
        </div>
        <?php
        /** @var \App\GoodsPackage $package */
        $modifiers = [
            \App\Packages\PriceModifier\PriceModifierService::REFERRAL_MODIFIER
        ];
        if (Auth::user() && Auth::user()->shouldShowGroupDiscount()) {
            $modifiers = [\App\Packages\PriceModifier\PriceModifierService::GROUP_MODIFIER] + $modifiers;
        }
        $arguments = ['user' => Auth::user()];
        ?>

        @if($good->getCheapestAvailablePackage() && $good->getMostExpensiveAvailablePackage())
            @if ($good->getCheapestAvailablePackage()->id === $good->getMostExpensiveAvailablePackage()->id)
                <p class="positions text-center">{{ $good->getCheapestAvailablePackage()->getHumanWeight() }}</p>
                <a href="{{ url($goodUrl) }}" class="btn btn-orange">{{ $good->getCheapestAvailablePackage()->getHumanPrice(null, $modifiers, $arguments) }}</a>
            @else
                <p class="positions text-center">от {{ $good->getCheapestAvailablePackage()->getHumanWeight() }} до {{ $good->getMostExpensiveAvailablePackage()->getHumanWeight() }}</p>
                <a href="{{ url($goodUrl) }}" class="btn btn-orange">от {{ $good->getCheapestAvailablePackage()->getHumanPrice(null, $modifiers, $arguments) }} до {{ $good->getMostExpensiveAvailablePackage()->getHumanPrice(null, $modifiers, $arguments) }}</a>
            @endif
        @else
            <p class="positions text-center">-</p>
            <a href="#" class="btn btn-orange">-</a>
        @endif
    </div> <!-- /.thumbnail -->
</div> <!-- /.card -->
<!-- /layouts/components/component-card -->
