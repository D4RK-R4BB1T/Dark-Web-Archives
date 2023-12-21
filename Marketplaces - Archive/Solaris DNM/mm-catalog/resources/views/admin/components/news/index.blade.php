<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>
<div>
    <a href="{{ $prefix }}/news/create" class="btn btn-success"><span class="glyphicon glyphicon-plus"></span> </a>
</div>

<p></p>

<div>
    {{ $news->links() }}
</div>

<div class="list-group">
    @foreach ($news as $n)
        <a href="{{ $prefix }}/news/edit?id={{ $n->id }}" class="list-group-item" id="news_id_{{ $n->id }}">
            <h4 class="list-group-item-heading">#{{ $n->id }} {{ $n->title }}</h4>
            <p class="list-group-item-text">{{ __('admin.Author') }} <b>{{ $n->author }}</b>; {{ __('admin.added') }} {{ $n->created_at }}, {{ __('admin.updated') }} {{ $n->updated_at }}</p>
        </a>
        <a href="{{ $prefix }}/news/destroy?id={{ $n->id }}" class="list-group-item"><span class="glyphicon glyphicon-minus"> {{ __('admin.delete') }}</span></a>
    @endforeach
</div>
<div>
    {{ $news->links() }}
</div>