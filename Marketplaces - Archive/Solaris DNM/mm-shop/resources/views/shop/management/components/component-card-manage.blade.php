<?php
if (!isset($good)) {
    throw new \Exception('Good is not set.');
}

if (isset($shop)) {
    $good->shop = $shop;
}

$preorderPackagesCount = $good->packages->filter(function($package) {
    return $package->preorder;
})->count();

$goodNeedCities = $good->cities->count() == 0;
$goodNeedPackages = $good->packages->count() == 0;
$goodNeedQuests = !$goodNeedPackages && $good->available_positions_count == 0 && $preorderPackagesCount == 0;
?>
<!-- shop/management/components/component-card-manage -->
<div class="col-xs-12 col-sm-6 card">
    <div class="thumbnail thumbnail-cover caption-slide">
        <div class="row management-icons">
            <div class="col-xs-10 col-sm-12 col-md-10 text-left no-padding-r">
                @can('management-quests-create', $good)
                    @if ($goodNeedQuests)
                        <a href="{{ url('/shop/management/goods/quests/add/'.$good->id) }}" class="text-danger fw-600 hint--top hint--error" aria-label="Необходимо добавить квесты!"><i class="glyphicon glyphicon-arrow-down"></i></a>
                    @else
                        <a href="{{ url('/shop/management/goods/quests/add/'.$good->id) }}" class="text-muted hint--top" aria-label="Добавить новый квест"><i class="glyphicon glyphicon-arrow-down"></i></a>
                    @endif
                @else
                    <a href="#" class="text-very-muted hint--top" aria-label="Добавление квеста недоступно"><i class="glyphicon glyphicon-arrow-down"></i></a>
                @endcan
                @if(Auth::user()->can('management-goods-edit', $good) || Auth::user()->can('management-quests-create', $good))
                    @if ($goodNeedPackages)
                        <a href="{{ url('/shop/management/goods/packages/add/'.$good->id) }}" class="text-danger fw-600 hint--top hint--error" aria-label="Необходимо добавить упаковки!">(0)</a>
                    @else
                        <a href="{{ url('/shop/management/goods/packages/'.$good->id) }}" class="text-muted hint--top" aria-label="Количество доступных квестов / упаковок с предзаказом">({{ $good->available_positions_count }}@if($preorderPackagesCount > 0)/{{ $preorderPackagesCount }}@endif)</a>
                    @endif
                @else
                    <a href="#" class="text-very-muted hint--top" aria-label="Просмотр упаковок недоступен">-</a>
                @endif
            </div>
            <div class="col-xs-10 col-sm-9 col-md-10 text-left no-padding">
                @can('management-sections-orders')
                    <?php $preordersCount = $good->orders->filter(function ($order) { return $order->status === \App\Order::STATUS_PREORDER_PAID; })->count(); ?>
                    <a href="{{ url('/shop/management/orders?good='.$good->id.'&status='.\App\Order::STATUS_PREORDER_PAID) }}" class="{{ $preordersCount > 0 ? 'text-danger fw-600' : 'text-muted' }} hint--top" aria-label="Ожидающие доставки предзаказы">
                        <i class="glyphicon glyphicon-time"></i> ({{ $preordersCount }})
                    </a>
                @else
                    <a href="#" class="text-very-muted hint--top" aria-label="Просмотр заказов недоступен">
                        <i class="glyphicon glyphicon-time"></i> -
                    </a>
                @endcan
            </div>
            <div class="col-xs-4 col-sm-3 col-md-4 text-right no-padding-l" style="padding-right: 8px">
                @can('management-goods-edit', $good)
                    <a href="{{ url('/shop/management/goods/edit/'.$good->id) }}" class="text-muted hint--top" aria-label="Редактировать"><i class="glyphicon glyphicon-edit"></i></a>
                @else
                    <a href="#" class="text-very-muted hint--top" aria-label="Редактирование товара недоступно"><i class="glyphicon glyphicon-edit"></i></a>
                @endcan
            </div>
        </div>
        <div class="slide">
            @if(Auth::user()->can('management-goods-edit', $good) || Auth::user()->can('management-quests-create', $good))
                @if ($goodNeedPackages)
                    <a href="{{ url('/shop/management/goods/packages/add/'.$good->id) }}">
                @else
                    <a href="{{ url('/shop/management/goods/packages/'.$good->id) }}">
                @endif
            @else
                <a target="_blank" href="{{url('/shop/'.$good->shop->slug.'/goods/'.$good->id) }}">
            @endif
                <img src="{{ url($good->image_url) }}" class="img-responsive">
            </a>
        </div>
        <div class="description">
            <h4><a class="dark-link" href="{{ url('/shop/'.$good->shop->slug.'/goods/'.$good->id) }}">{{ $good->title }}</a></h4>
            {{--<h5><i class="glyphicon glyphicon-user"></i>&nbsp;&nbsp;<a href="{{ url('/shop/'.$good->shop->slug) }}">{{ $good->shop->title }}</a></h5>--}}


            <div class="row">
                <div class="col-xs-2 col-sm-4 col-md-3 col-lg-2"><i class="glyphicon glyphicon-map-marker"></i></div>
                <div class="col-xs-22 col-sm-20 col-md-21 col-lg-22">
                @if ($good->cities->count() > 0)
                    @foreach ($good->cities as $city)
                        <a style="font-weight: 500" href="?city={{ $city->id }}">{{ $city->title }}</a>@if (!$loop->last) &bull; @endif
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

        @if ($goodNeedCities)
            <p class="positions text-center">-</p>
            @can('management-goods-edit', $good)
                <a href="{{ url('/shop/management/goods/cities/'.$good->id) }}" class="btn btn-danger">Необходимо добавить города!</a>
            @else
                <a href="#" class="btn btn-danger hint--top" style="opacity: 0.7" aria-label="Добавление городов недоступно">Необходимо добавить города!</a>
            @endcan
        @elseif($goodNeedPackages)
            <p class="positions text-center">-</p>
            @can('management-goods-edit', $good)
                <a href="{{ url('/shop/management/goods/packages/'.$good->id) }}" class="btn btn-danger">Необходимо добавить упаковки!</a>
            @else
                <a href="#" class="btn btn-danger hint--top" style="opacity: 0.7" aria-label="Добавление упаковок недоступно">Необходимо добавить упаковки!</a>
            @endcan
        @elseif($goodNeedQuests)
            <p class="positions text-center">-</p>
            @can('management-quests-create', $good)
                <a href="{{ url('/shop/management/goods/quests/add/'.$good->id) }}" class="btn btn-danger">Необходимо добавить квесты!</a>
            @else
                <a href="#" class="btn btn-danger hint--top" style="opacity: 0.7" aria-label="Добавление квеста недоступно">Необходимо добавить квесты!</a>
            @endcan
        @else
            @if ($good->getCheapestPackage()->id === $good->getMostExpensivePackage()->id)
                <p class="positions text-center">{{ $good->getCheapestPackage()->getHumanWeight() }}</p>
                <a href="{{ url('/shop/'.$good->shop->slug.'/goods/'.$good->id) }}" class="btn btn-orange">{{ $good->getCheapestPackage()->getHumanPrice() }}</a>
            @else
                <p class="positions text-center">от {{ $good->getCheapestPackage()->getHumanWeight() }} до {{ $good->getMostExpensivePackage()->getHumanWeight() }}</p>
                <a href="{{ url('/shop/'.$good->shop->slug.'/goods/'.$good->id) }}" class="btn btn-orange">от {{ $good->getCheapestPackage()->getHumanPrice() }} до {{ $good->getMostExpensivePackage()->getHumanPrice() }}</a>
            @endif
        @endif
    </div> <!-- /.thumbnail -->
</div> <!-- /.card -->
<!-- / shop/management/components/component-card-manage -->
