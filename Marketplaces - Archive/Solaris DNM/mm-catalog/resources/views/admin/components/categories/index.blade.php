<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>
{{--<div>--}}
    {{--<a href="{{ $prefix }}/categories/add" class="btn btn-success"><span class="glyphicon glyphicon-plus"></span> </a>--}}
{{--</div>--}}

<div class="just-padding">
    <div class="list-group list-group-root well">
@foreach ($categories_main as $i=>$cat)
            {{--<a href="{{ $prefix }}/categories/edit?id={{ $cat->id }}" class="list-group-item"><b>#{{ $cat->id }} {{ $cat->title }} <sup>{{ $cat->priority }}</sup></b></a>--}}
            <a href="{{ $prefix }}/categories/view?id={{ $cat->id }}" class="list-group-item"><b>#{{ $cat->id }} {{ $cat->title }} <sup>{{ $cat->priority }}</sup></b></a>
            {{--<a href="{{ $prefix }}/delete_{{ $category }}&id={{ $cat->id }}" class="list-group-item"><span class="glyphicon glyphicon-minus"> delete</span></a>--}}
            @foreach ($categories_children as $ii=>$subcat)
                @if($subcat->parent_id === $cat->id)
                    <div class="list-group">
                        {{--<a href="{{ $prefix }}/edit_categories?id={{ $subcat->id }}" class="list-group-item">#{{ $subcat->id }} {{ $subcat->title }} <sup>{{ $subcat->priority }}</sup></a>--}}
                        <a href="{{ $prefix }}/categories/view?id={{ $subcat->id }}" class="list-group-item">#{{ $subcat->id }} {{ $subcat->title }} <sup>{{ $subcat->priority }}</sup></a>
{{--                        <a href="{{ $prefix }}/delete_{{ $category }}?id={{ $subcat->id }}" class="list-group-item"><span class="glyphicon glyphicon-minus"> delete</span></a>--}}
                    </div>
                @endif
            @endforeach
@endforeach
    </div>
</div>