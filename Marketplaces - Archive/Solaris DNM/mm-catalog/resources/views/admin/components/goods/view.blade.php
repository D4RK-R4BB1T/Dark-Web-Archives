<div class="form-group">
    <label for="app_id">{{ __('admin.App id') }}</label>
    <input class="form-control" id="app_id" name="app_id" value="{{ $good->app_id }}" readonly />
</div>

<div class="form-group">
    <label for="app_good_id">{{ __('admin.App goods id') }}</label>
    <input class="form-control" id="app_good_id" name="app_good_id" value="{{ $good->app_good_id }}" readonly />
</div>

<div class="form-group">
    <label for="title">{{ __('admin.Title') }}</label>
    <input class="form-control" id="title" name="title" value="{{ $good->title }}" readonly />
</div>

<div class="form-group">
    <label for="category_id">{{ __('layout.Category') }}</label>
    <select class="form-control" id="category_id" name="category_id" disabled>
        @foreach ($categories_main as $cat)
            <optgroup label="{{ $cat->title }}">
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
    <select class="form-control" id="city_id" name="city_id" disabled>
        @foreach ($cities as $city)
            <option value="{{ $city->id }}" @if($good->city_id == $city->id)selected="selected"@endif>{{ $city->title }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="description">{{ __('layout.Description') }}</label>
    <textarea class="form-control" id="description" name="description" rows="3" readonly>{{ $good->description }}</textarea>
</div>

<div class="form-group">
    <label for="image_url_local">{{ __('admin.Image url local') }}</label>
    <input class="form-control" id="image_url_local" name="image_url_local" value="{{ $good->image_url_local }}" readonly />
</div>

<div class="form-group">
    <label for="image_url">{{ __('layout.Image url') }}</label>
    <input class="form-control" id="image_url" name="image_url" value="{{ $good->image_url }}" readonly />
</div>

<div class="form-group">
    <input class="form-check-input" type="checkbox" {{ $good->image_cached ? 'checked' : '' }} id="image_cached" name="image_cached" disabled> {{ __('admin.Image cached') }}
</div>

<div class="form-group">
    <input class="form-check-input" type="checkbox" {{ $good->has_quests ? 'checked' : '' }} id="has_quests" name="has_quests" disabled> {{ __('admin.Has quests') }}
</div>

<div class="form-group">
    <input class="form-check-input" type="checkbox" {{ $good->has_ready_quests ? 'checked' : '' }} id="has_ready_quests" name="has_ready_quests" disabled> {{ __('admin.Has instant quests') }}
</div>

<div class="form-group">
    <label for="buy_count">{{ __('admin.Buy count') }}</label>
    <input class="form-control" id="buy_count" name="buy_count" type="number" min="0" max="2147483648" value="{{ $good->buy_count }}" readonly>
</div>

<div class="form-group">
    <label for="reviews_count">{{ __('admin.Review count') }}</label>
    <input class="form-control" id="reviews_count" name="reviews_count" type="number" min="0" max="2147483648" value="{{ $good->reviews_count }}" readonly>
</div>

<div class="form-group">
    <label for="rating">{{ __('layout.Rating') }}</label>
    <input class="form-control" id="rating" name="rating" type="number" step="0.1" value="{{ $good->rating }}" readonly>
</div>

<div class="form-group">
    <label for="created_at">{{ __('layout.Created') }}</label>
    <input class="form-control" id="created_at" name="created_at" value="{{ $good->created_at }}" readonly>
</div>

<div class="form-group">
    <label for="updated_at">{{ __('admin.Updated') }}</label>
    <input class="form-control" id="updated_at" name="updated_at" value="{{ $good->updated_at }}" readonly>
</div>
