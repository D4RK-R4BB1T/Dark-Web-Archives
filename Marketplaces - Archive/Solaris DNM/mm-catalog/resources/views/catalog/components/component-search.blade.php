<?php
//$cities = \App\Good::available()
//    ->with(['cities'])
//    ->get()
//    ->flatMap(function ($good) { return $good->cities; })
//    ->unique('id')
//    ->sortByDesc('priority');

$cities = \App\City::allCached();
$hasRegions = in_array(request('city'), \App\City::citiesWithRegions());
if ($hasRegions) {
    $availableRegionsIds = \App\GoodsPosition::whereNotNull('region_id')->pluck('region_id')->unique();
    $city = \App\City::find(request('city'));
    $availableRegions = $city->regions()->whereIn('id', $availableRegionsIds)->get();
}
?>
<!-- layouts/components/component-search -->
<form role="form" action="" method="get">
    <input type="hidden" name="category" value="{{ request('category') }}">
    <div class="row">
        <div class="col-xs-24 col-sm-{{ $hasRegions ? 7 : 7 }}">
            <div class="form-group has-feedback">
                <input class="form-control" name="query" placeholder="{{ __('layout.Search term') }}"
                       value="{{ request('query') }}"/>
                <span class="glyphicon glyphicon-search form-control-feedback"></span>
            </div>
        </div>
        <div class="col-xs-24 col-sm-{{ $hasRegions ? 5 : 5 }}">
            <div class="form-group has-feedback">
                <select name="city" class="form-control" title="{{ __('layout.Choose city') }}">
                    <option value="">{{ __('layout.Any city') }}</option>
                    @foreach($cities as $city)
                        <option value="{{ $city['id'] }}" {{ request('city') == $city['id'] ? 'selected' : '' }}>{{ $city['title'] }}</option>
                    @endforeach
                </select>
                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
            </div>
        </div>
        @if ($hasRegions)
            <div class="col-xs-24 col-sm-5">
                <div class="form-group has-feedback">
                    <select name="region" class="form-control" title="{{ __('layout.Choose area') }}">
                        <option value="">{{ __('layout.Any area') }}</option>
                        @foreach($availableRegions as $region)
                            <option value="{{ $region->id }}" {{ request('region') == $region->id ? 'selected' : '' }}>{{ $region->title }}</option>
                        @endforeach
                    </select>
                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                </div>
            </div>
        @endif
        {{--<div class="col-xs-24 col-sm-5">--}}
        {{--<div class="form-group transparent has-feedback">--}}
        {{--<select name="sort" class="form-control" title="Выберите способ сортировки...">--}}
        {{--<option value="test">Рекомендованный</option>--}}
        {{--</select>--}}
        {{--<span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>--}}
        {{--</div>--}}
        {{--</div>--}}
        <div class="col-xs-24 col-sm-4">
            <div class="form-group transparent has-feedback">
                <select name="availability" class="form-control" title="{{ __('layout.Select type of quest') }}">
                    <option value="all">{{ __('layout.All') }}</option>
                    <option value="ready" {{ request('availability') == 'ready' ? 'selected' : '' }}>{{ __('layout.Ready only') }}</option>
                </select>
                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
            </div>
        </div>
        <div class="col-xs-24 col-sm-offset-{{ $hasRegions ? 0 : 5 }} col-sm-3 text-right">
            <div class="form-group" style="height: 33px; line-height: 32px;">
                <button class="btn btn-orange" type="submit">{{ __('layout.Search') }}</button>
            </div>
        </div>
    </div> <!-- /.row -->
</form>
<!-- / layouts/components/component-search -->