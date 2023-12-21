<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>
<form role="form" method="POST" action="{{ url($prefix . '/news/update?id=' . $news->id) }}">
    {{ csrf_field() }}
    <div class="form-group">
        <label for="title">{{ __('admin.Title') }}</label>
        <input class="form-control" id="title" name="title" value="{{ $news->title }}" />
    </div>

    <div class="form-group">
        <label for="text">{{ __('admin.News text') }}</label>
        <textarea class="form-control" id="text" name="text" rows="6">{{ $news->text }}</textarea>
    </div>

    <div class="form-group">
        <label for="author">{{ __('admin.Author') }}</label>
        <input class="form-control" id="author" name="author" value="{{ $news->author }}" />
    </div>

    <button type="submit" class="btn btn-primary">{{ __('admin.Add') }}</button>
</form>