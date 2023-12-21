{{-- 
This file is part of MM2-dev project. 
Description: Good page
--}}
@extends('layouts.master')

@section('title', $good->title)

@section('content')
    @include('layouts.components.sections-menu')
    @if (Auth::check() && Auth::user()->employee)
        @include('shop.management.components.sections-menu')
    @endif

    @include('layouts.components.sections-breadcrumbs', [
        'breadcrumbs' =>
        [
            // BREADCRUMB_CATALOG,
            // BREADCRUMB_SHOPS,
            ['title' => $shop->title, 'url' => url('/shop/' . $shop->slug)],
            ['title' => $good->title]
        ],
        'left_column_width' => [6, 7, 5, 5] // col-xs-6 col-sm-7 col-md-5 col-lg-5
    ])

    <div class="row">
        <div class="col-sm-7 col-md-5 col-lg-5">
            @include('shop.sidebar')
        </div> <!-- /.col-lg-5 -->
        <!-- positions (pull-right) -->
        <div class="col-xs-24 col-sm-17 col-md-8 col-lg-8 pull-right animated fadeIn">
            <div class="panel panel-default panel-sidebar block no-padding">
                <div class="panel-heading">
                    <div class="row">
                        @if (!$cityId)
                            <div class="col-xs-24">Просмотр товаров</div>
                        @else
                            <div class="col-xs-12">Фасовка</div>
                            <div class="col-xs-6 col-xs-offset-3 col-md-offset-2 col-lg-offset-3 text-center">Стоимость</div>
                        @endif
                    </div>
                </div>
                <div class="panel-body">
                    @if (!$cityId)
                        <form role="form" action="" method="get">
                            @if($review_city_id = request()->get('review_city'))
                                <input type="hidden" name="review_city" value="{{ $review_city_id }}">
                            @endif
                            <div class="row">
                                <div class="col-xs-19">
                                    <div class="form-group has-feedback">
                                        <select name="city" class="form-control" title="Выберите город...">
                                            <option value="">Выберите ваш город</option>
                                            @foreach($availableCities as $availableCity)
                                                <option value="{{ $availableCity->id }}">{{ $availableCity->title }}</option>
                                            @endforeach
                                        </select>
                                        <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                    </div>
                                </div>
                                <div class="col-xs-5">
                                    <div class="form-group">
                                        <button class="btn btn-orange" type="submit">Найти</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    @else
                        <p class="text-muted text-center">
                            Отображаются товары в городе <strong>{{ $selectedCity->title }}</strong>.
                            @if ($hasOtherCities)
                                <br />
                                <a href="?">Выбрать другой город »</a>
                            @endif
                        </p>
                        <hr class="small" />
                        @if ($packages->count() > 0)
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

                            @foreach ($packages->groupBy('city_id') as $cityId => $packages)
                                @foreach($packages as $package)
                                    <div class="row">
                                        <div class="col-xs-7 col-sm-9 col-md-8">&nbsp;{{ $package->getHumanWeight() }}</div>
                                        <div class="col-xs-8 col-sm-8 col-md-8 text-right">{{ $package->getHumanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB, $modifiers, $arguments) }}</div>
                                        <div class="col-xs-8 col-xs-offset-1 col-sm-7 col-sm-offset-0 col-md-8 text-center">{{ $package->getHumanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_BTC, $modifiers, $arguments) }}</div>
                                    </div>
                                    <div class="list-group hover-menu">
                                        <ul class="list-group-item-submenu">
                                            @if ($package->preorder)
                                                <li><a class="dark-link" href="{{ url('/shop/'.$shop->slug.'/goods/'.$good->id.'/'.$package->id) }}">Предзаказ</a></li>
                                            @else
                                                @foreach ($package->availablePositions->unique(function($position) {
                                                        $regionId = $position->region ? $position->region->id : 0;
                                                        $customPlaceId = $position->customPlace ? $position->customPlace->id : 0;
                                                        return $regionId . '_' . $customPlaceId;
                                                    })->values()->sortBy('customPlace') as $position)
                                                    @if (!$position->region && !$position->customPlace)
                                                        <li><a class="dark-link" href="{{ url('/shop/'.$shop->slug.'/goods/'.$good->id.'/'.$package->id) }}">Район не указан</a></li>
                                                    @else
                                                        @if ($position->region)
                                                            <li><a class="dark-link" href="{{ url('/shop/'.$shop->slug.'/goods/'.$good->id.'/'.$package->id.'?subregion_id='.$position->region->id) }}">{{ $position->region->title }}</a></li>
                                                        @elseif($position->customPlace)
                                                            <li><a class="dark-link" href="{{ url('/shop/'.$shop->slug.'/goods/'.$good->id.'/'.$package->id.'?custom_place_id='.$position->customPlace->id) }}">{{ $position->customPlace->title }}</a></li>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            @endif
                                        </ul>
                                    </div>
                                @endforeach
                                @if (!$loop->last)
                                    <hr class="small" />
                                @endif
                            @endforeach
                        @else
                            <div class="alert alert-warning" style="margin-bottom: 0">Нет доступных позиций.</div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        <!-- / positions -->
        <!-- info (pull-left) -->
        <div class="col-xs-24 col-sm-24 col-md-11 col-lg-11 pull-left animated fadeIn">
            <div class="well block good-info">
                <h3>{{ $good->title }}</h3>
                <p>
                    <i class="glyphicon glyphicon-map-marker"></i>
                    @if ($good->cities->count() > 0)
                        @foreach ($good->cities as $city)
                            {{ $city->title }}@if (!$loop->last) <span class="text-muted">&bull;</span> @endif
                        @endforeach
                    @else
                        -
                    @endif
                </p>
                <div class="row">
                    <div class="col-xs-12">
                        <p>Рейтинг товара: <span class="text-orange">{{ $good->getRating() }}</span></p>
                    </div>
                    <div class="col-xs-12 text-right">
                        <p>Количество отзывов о товаре: <span class="text-orange">{{ $good->reviews->count() }}</span></p>
                    </div>
                </div>
                <div class="good-photos">
                    <?php $photos = $good->photos; ?>
                    @if($photos->count() === 0)
                        <div class="good-photo-main">
                            <a href="{{ url($good->image_url) }}" target="_blank">
                                <img src="{{ url($good->image_url) }}" />
                            </a>
                        </div>
                    @else
                        <div class="good-photo-main multiply">
                            <a href="{{ url($good->photos[0]->image_url) }}" target="_blank">
                                <img src="{{ url($good->photos[0]->image_url) }}" />
                            </a>
                        </div>
                        @if (count($good->photos) > 1)
                            @for ($i = 1; $i < count($good->photos); $i++)
                            <div class="good-photo-additional">
                                {{--<img src="{{ $good->photos[$i]->image_url ?: '' }}" />--}}
                            </div>
                            @endfor
                        @endif
                    @endif
                </div>
                &nbsp;
                <p>{!! nl2br(e($good->description)) !!}</p>
            </div>

            <div class="well block">
                <h3>Отзывы</h3>
                <hr class="small" />

                <form action="" method="get">
                    @if($cityId)
                        <input type="hidden" name="city" value="{{ $cityId }}">
                    @endif

                    <div class="row">
                        <div class="col-xs-4">
                            <label for="review_city" class="margin-top-8">Город:</label>
                        </div>
                        <div class="col-xs-15 text-right">
                            <div class="form-group has-feedback">
                                <select name="review_city" id="review_city" class="form-control">
                                    <option value="">Выберите город</option>
                                    @foreach($good->cities as $city)
                                        <option value="{{ $city->id }}" @if($city->id == request()->get('review_city'))selected @endif>{{ $city->title }}</option>
                                    @endforeach
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                            </div>
                        </div>

                        <div class="col-xs-5 pull-right">
                            <button type="submit" class="btn btn-orange" style="width: 100%">Показать</button>
                        </div>
                    </div>
                </form>

                <hr class="small" />
                @if ($reviews->count() > 0)
                    @foreach ($reviews as $review)
                        @include('layouts.components.component-review', ['review' => $review, 'showCity' => true])
                        @if(!$loop->last)
                            <hr class="small" />
                        @endif
                    @endforeach
                    @if ($reviews->total() > $reviews->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $reviews->appends(request()->input())->links() }}
                        </div>
                    @endif
                @else
                    <div class="alert alert-info" style="margin-bottom: 0">Отзывов еще нет.</div>
                @endif
            </div>
        </div>
        <!-- / info -->
    </div> <!-- /.row -->

@endsection
