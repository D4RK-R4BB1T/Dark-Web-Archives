<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>
{{--<div>--}}
    {{--<a href="{{ $prefix }}/cities/create" class="btn btn-success"><span class="glyphicon glyphicon-plus"></span> </a>--}}
{{--</div>--}}

<p></p>

<div>
    {{ $cities->links() }}
</div>

<div class="list-group">
@foreach ($cities as $city)
        {{--<a href="{{ $prefix }}/cities/edit?id={{ $city->id }}" class="list-group-item" id="city_id_{{ $city->id }}">--}}
        <a href="{{ $prefix }}/cities/view?id={{ $city->id }}" class="list-group-item" id="city_id_{{ $city->id }}">
            <h4 class="list-group-item-heading">#{{ $city->id }} {{ $city->title }}</h4>
            <p class="list-group-item-text">{{ $city->priority }}</p>
        </a>
{{--        <a href="{{ $prefix }}/cities/destroy?id={{ $city->id }}" class="list-group-item"><span class="glyphicon glyphicon-minus"> delete</span></a>--}}
@endforeach
</div>
<div>
    {{ $cities->links() }}
</div>