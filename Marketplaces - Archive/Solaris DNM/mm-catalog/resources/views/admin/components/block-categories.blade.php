@php
$prefix = isset($prefix) ? $prefix : '/admin';
$selectedCategory = $category;
@endphp
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">{{ __('admin.Categories') }}</div>
    <div class="panel-body no-padding">
        @if (count($cats) > 0)
            <div class="list-group hover-menu">
                @foreach ($cats as $category_key => $category_name)
                    <div class="list-group-item">
                        <a class="dark-link {{ $category_key == $selectedCategory ? 'selected' : '' }}" href="{{ $prefix }}/{{ $category_key }}">{{ $category_name }}</a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info" style="margin-bottom: 0">No categories available</div>
        @endif
    </div>
</div>