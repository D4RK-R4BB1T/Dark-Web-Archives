<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>
<form role="form" method="POST" action="{{ url($prefix . '/cities/update?id=' . $city->id) }}">
    {{ csrf_field() }}
    <div class="form-group">
        <label for="title">{{ __('admin.Title') }}</label>
        <input class="form-control" id="title" name="title" value="{{ $city->title }}" />
    </div>

    <div class="form-group">
        <label for="priority">{{ __('admin.Priority') }}</label>
        <input class="form-control" id="priority" name="priority" type="number" min="-2147483648" max="2147483648" value="{{ $city->priority }}">
    </div>

    <button type="submit" class="btn btn-primary">{{ __('admin.Update') }}</button>
</form>
