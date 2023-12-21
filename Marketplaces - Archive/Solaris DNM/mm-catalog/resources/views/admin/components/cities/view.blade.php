<div class="form-group">
    <label for="title">{{ __('admin.Title') }}</label>
    <input class="form-control" id="title" name="title" value="{{ $city->title }}" readonly />
</div>

<div class="form-group">
    <label for="priority">{{ __('admin.Priority') }}</label>
    <input class="form-control" id="priority" name="priority" type="number" min="-2147483648" max="2147483648" value="{{ $city->priority }}" readonly>
</div>
