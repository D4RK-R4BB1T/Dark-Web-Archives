<?php
$prefix = $prefix ?? '/admin';
?>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form role="form" method="POST" action="{{ url($prefix . '/advstats/' . $stats->id) }}">
    {{ csrf_field() }}
    <input type="hidden" name="_method" value="PUT">
    <div class="form-group">
        <label for="title">{{ __('admin.Title') }}</label>
        <input class="form-control" id="title" name="title" value="{{ $stats->title }}" required>
    </div>

    <button class="btn btn-primary">Сохранить</button>
</form>