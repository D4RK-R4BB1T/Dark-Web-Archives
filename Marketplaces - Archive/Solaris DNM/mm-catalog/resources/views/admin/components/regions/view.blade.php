<div class="form-group">
    <label for="city_id">{{ __('layout.City') }}</label>
    <select class="form-control" id="city_id" name="city_id" disabled>
        @foreach ($cities as $city)
            <option value="{{ $city->id }}" @if($region->city_id == $city->id)selected="selected"@endif>{{ $city->title }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="title">{{ __('admin.Title') }}</label>
    <input class="form-control" id="title" name="title" value="{{ $region->title }}" readonly/>
</div>

<div class="form-group">
    <label for="priority">{{ __('admin.Priority') }}</label>
    <input class="form-control" id="priority" name="priority" type="number" min="-2147483648" max="2147483648" value="{{ $region->priority }}" readonly>
</div>
