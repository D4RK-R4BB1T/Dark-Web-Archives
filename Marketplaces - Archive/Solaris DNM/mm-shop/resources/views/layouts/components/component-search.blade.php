@php
$hasRegions = in_array(request('city'), \App\City::citiesWithRegions());
if (isset($shop) && !is_null($shop)) {
    $goods = isset($show_all) ? $shop->goods() : $shop->availableGoods();
    $cities = $goods
        ->with(['cities'])
        ->get()
        ->flatMap(function ($good) { return $good->cities; })
        ->unique('id')
        ->sortByDesc('priority');
} else {
    $cities = \App\City::allCached();
}
@endphp
<!-- layouts/components/component-search -->
<form role="form" action="" method="get">
    <input type="hidden" name="category" value="{{ request('category') }}">
    <div class="row">
        <div class="col-xs-24 col-sm-{{ $hasRegions ? 7 : 7 }}">
            <div class="form-group has-feedback">
                <input class="form-control" name="query" placeholder="Я ищу..." value="{{ request('query') }}"/>
                <span class="glyphicon glyphicon-search form-control-feedback"></span>
            </div>
        </div>
        <div class="col-xs-24 col-sm-{{ $hasRegions ? 5 : 5 }}">
            <div class="form-group has-feedback">
                <select name="city" class="form-control" title="Выберите город...">
                    <option value="">Любой город</option>
                    @foreach($cities as $city)
                        @if(is_object($city))
                            <option value="{{ $city->id }}" {{ request('city') == $city->id ? 'selected' : '' }}>{{ $city->title }}</option>
                        @endif
                    @endforeach
                </select>
                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
            </div>
        </div>
        @if ($hasRegions)
        <div class="col-xs-24 col-sm-5">
            <div class="form-group has-feedback">
                <select name="region" class="form-control" title="Выберите район...">
                    <option value="">Любой район</option>
                    @foreach(\App\City::find(request('city'))->regions as $region)
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
                <select name="availability" class="form-control" title="Выберите тип клада...">
                    <option value="all">Все</option>
                    <option value="ready" {{ request('availability') == 'ready' ? 'selected' : '' }}>Готовый адрес</option>
                </select>
                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
            </div>
        </div>
        <div class="col-xs-24 col-sm-offset-{{ $hasRegions ? 0 : 5 }} col-sm-3 text-right">
            <div class="form-group">
                <button class="btn btn-orange" type="submit">Поиск</button>
            </div>
        </div>
    </div> <!-- /.row -->
</form>
<!-- / layouts/components/component-search -->