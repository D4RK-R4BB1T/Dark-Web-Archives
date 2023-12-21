<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>
{{--<div>--}}
    {{--<a href="{{ $prefix }}/add_regions" class="btn btn-success"><span class="glyphicon glyphicon-plus"></span> </a>--}}
{{--</div>--}}

<p></p>

<div>
    {{ $regions->links() }}
</div>

<div class="list-group">
    @foreach ($regions as $region)
        {{--<a href="{{ $prefix }}/edit_regions?id={{ $region->id }}" class="list-group-item" id="region_id_{{ $region->id }}">--}}
        <a href="{{ $prefix }}/view_regions?id={{ $region->id }}" class="list-group-item" id="region_id_{{ $region->id }}">
            <h4 class="list-group-item-heading">#{{ $region->id }} {{ $region->title }}</h4>
            <p class="list-group-item-text">
                @foreach ($cities as $city)
                    @if ($city->id == $region->city_id)
                        {{ $city->title }}
                        @break
                    @endif
                @endforeach
            </p>
        </a>
        {{--<a href="{{ $prefix }}/delete_{{ $category }}?id={{ $region->id }}" class="list-group-item"><span class="glyphicon glyphicon-minus"> delete</span></a>--}}
    @endforeach
</div>
<div>
    {{ $regions->links() }}
</div>