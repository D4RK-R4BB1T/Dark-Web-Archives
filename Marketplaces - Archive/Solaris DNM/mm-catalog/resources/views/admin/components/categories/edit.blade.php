<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>
<form role="form" method="POST" action="{{ url($prefix . '/categories/update?id=' . $edit_category->id) }}">
    {{ csrf_field() }}
    <div class="form-group">
        <label for="title">{{ __('admin.Title') }}</label>
        <input class="form-control" id="title" name="title" value="{{ $edit_category->title }}" />
    </div>

    <div class="form-group">
        <label for="parent_id">{{ __('admin.Parent category') }}</label>
        <select class="form-control" id="parent_id" name="parent_id">
            <option value="" @if(!$edit_category->parent_id) selected="selected" @endif>-</option>
            @foreach($categories_main as $cm)
            <option value="{{ $cm->id }}" @if($edit_category->parent_id == $cm->id) selected="selected" @endif>{{ $cm->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="priority">{{ __('admin.Priority') }}</label>
        <input class="form-control" id="priority" name="priority" type="number" min="-2147483648" max="2147483648" value="{{ $edit_category->priority }}">
    </div>

    <button type="submit" class="btn btn-primary">{{ __('admin.Update') }}</button>
</form>
