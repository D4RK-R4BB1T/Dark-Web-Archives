<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>
<form role="form" method="POST" action="{{ url($prefix . '/goods/update?id=' . $good->id) }}">
    {{ csrf_field() }}
    <div class="form-group">
        <label for="title">{{ __('admin.Title') }}</label>
        <input class="form-control" id="title" name="title" value="{{ $good->title }}" />
    </div>

    {{--<div class="form-group">--}}
        {{--<label for="shop_id">Магазин</label>--}}
        {{--<select class="form-control" id="shop_id" name="shop_id">--}}
            {{--@foreach ($shops as $shop)--}}
                {{--<option value="{{ $shop->id }}" {{ $good->shop_id == $shop->id ? 'active=active' : '' }}>{{ $shop->title }}</option>--}}
            {{--@endforeach--}}
        {{--</select>--}}
    {{--</div>--}}

    <div class="form-group">
        <label for="category_id">{{ __('layout.Category') }}</label>
        <select class="form-control" id="category_id" name="category_id">
            @foreach ($categories_main as $cat)
                <optgroup label="{{ $cat->title }}">
                    {{--<option value="{{ $cat->id }}" @if($good->category_id == $cat->id)selected="selected"@endif>{{ $cat->title }}</option>--}}
                    @foreach ($categories_children as $child)
                        @if ($child->parent_id == $cat->id)
                            <option value="{{ $child->id }}" @if($good->category_id == $child->id)selected="selected"@endif>{{ $child->title }}</option>
                        @endif
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="city_id">{{ __('layout.City') }}</label>
        <select class="form-control" id="city_id" name="city_id">
            @foreach ($cities as $city)
                <option value="{{ $city->id }}" @if($good->city_id == $city->id)selected="selected"@endif>{{ $city->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="description">{{ __('layout.Description') }}</label>
        <textarea class="form-control" id="description" name="description" rows="3">{{ $good->description }}</textarea>
    </div>

    <div class="form-group">
        <label for="image_url">{{ __('layout.Image url') }}</label>
        <input class="form-control" id="image_url" name="image_url" value="{{ $good->image_url }}" />
    </div>

    <div class="form-group">
        <input class="form-check-input" type="checkbox" {{ $good->has_quests ? 'checked' : '' }} id="has_quests" name="has_quests"> {{ __('admin.Has quests') }}
    </div>

    <div class="form-group">
        <input class="form-check-input" type="checkbox" {{ $good->has_ready_quests ? 'checked' : '' }} id="has_ready_quests" name="has_ready_quests"> {{ __('admin.Has instant quests') }}
    </div>

    {{--<div class="form-group">--}}
        {{--<label for="image_url_local">Картинка</label>--}}
        {{--<input type="file" class="form-control-file" id="image_url_local" aria-describedby="fileHelp">--}}
        {{--<small id="fileHelp" class="form-text text-muted"></small>--}}
    {{--</div>--}}

    <div class="form-group">
        <label for="buy_count">{{ __('layout.Buy count') }}</label>
        <input class="form-control" id="buy_count" name="buy_count" type="number" min="0" max="2147483648" value="{{ $good->buy_count }}">
    </div>

    <div class="form-group">
        <label for="reviews_count">{{ __('admin.Review count') }}</label>
        <input class="form-control" id="reviews_count" name="reviews_count" type="number" min="0" max="2147483648" value="{{ $good->reviews_count }}">
    </div>

    <div class="form-group">
        <label for="rating">{{ __('layout.Rating') }}</label>
        <input class="form-control" id="rating" name="rating" type="number" step="0.1" value="{{ $good->rating }}">
    </div>

    <button type="submit" class="btn btn-primary">{{ __('admin.Update') }}</button>
</form>
