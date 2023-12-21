<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>
{{--<div>
    <a href="{{ $prefix }}/goods/create" class="btn btn-success"><span class="glyphicon glyphicon-plus"></span> </a>
</div>

<p></p>--}}

<div>
    {{ $goods->links() }}
</div>

<div class="list-group">
@foreach ($goods as $good)
    {{-- <a href="{{ $prefix }}/goods/edit?id={{ $good->id }}" class="list-group-item" id="good_id_{{ $good->id }}"> --}}
    <a href="{{ $prefix }}/goods/view?id={{ $good->id }}" class="list-group-item">
        <h4 class="list-group-item-heading">#{{ $good->id }} {{ $good->title }}</h4>
        <p class="list-group-item-text">{{ $good->description }}</p>
    </a>
    {{-- <a href="{{ $prefix }}/goods/destroy?id={{ $good->id }}" class="list-group-item"><span class="glyphicon glyphicon-minus"> delete</span></a>  --}}
@endforeach
</div>
<div>
    {{ $goods->links() }}
</div>