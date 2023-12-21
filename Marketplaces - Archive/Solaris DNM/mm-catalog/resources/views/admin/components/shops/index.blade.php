@php
$prefix = isset($prefix) ? $prefix : '/admin';
@endphp

@include('shops.components.component-search')

<div>
    {{ $shops->links() }}
</div>

<div class="list-group">
    @foreach ($shops as $shop)
        {{--<a href="{{ $prefix }}/edit_shops?id={{ $shop->id }}" class="list-group-item" id="shop_id_{{ $shop->id }}">--}}
        <a href="{{ $prefix }}/view_shops?id={{ $shop->id }}" class="list-group-item" id="shop_id_{{ $shop->id }}">
            <h4 class="list-group-item-heading">#{{ $shop->id }} {{ $shop->title }}</h4>
            <p class="list-group-item-text">
                {{ $shop->url }}; {{ __('layout.Users') }}: {{ $shop->users_count }}; {{ __('admin.Orders count') }}: {{ $shop->orders_count }};
                @if($shop->enabled)
                    {{ mb_ucfirst(__('layout.enabled m')) }}
                @else
                    {{ mb_ucfirst(__('layout.disabled m')) }}
                @endif
            </p>
        </a>
{{--        <a href="{{ $prefix }}/delete_{{ $category }}?id={{ $shop->id }}" class="list-group-item"><span class="glyphicon glyphicon-minus"> delete</span></a>--}}
    @endforeach
</div>
<div>
    {{ $shops->links() }}
</div>