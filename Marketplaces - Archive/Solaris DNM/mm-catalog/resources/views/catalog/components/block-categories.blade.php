<?php
$prefix = isset($prefix) ? $prefix : '/catalog';

$availableChildCategoriesIds = \App\Good::available()
//    ->filterCity(request('city'))
    ->pluck('category_id')
    ->unique()
    ->toArray();

$availableParentCategories = \App\Category::getById($availableChildCategoriesIds)
    ->map(function($category) {
        return $category->parent();
    })
    ->unique();

$selectedCategory = request('category');
?>
<!-- layouts/components/block-categories -->
<!-- prefix: {{ $prefix }} -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">{{ __('layout.Categories') }}</div>
    <div class="panel-body no-padding">
        @if (count($availableParentCategories) > 0)
        <div class="list-group hover-menu">
            @foreach ($availableParentCategories as $category)
                <?php
                    $children = $category->children()->whereIn('id', $availableChildCategoriesIds);
                    $isOpen = $category->id == $selectedCategory || $children->pluck('id')->contains($selectedCategory);
                ?>
                <div class="list-group-item">
                    <a class="dark-link {{ $category->id == $selectedCategory ? 'selected' : '' }}" href="{{ $prefix }}?category={{ $category->id }}&city={{ request('city') }}">{{ $category->title }}</a>
                    <ul class="list-group-item-submenu {{ $isOpen ? '' : 'hidden' }} animated fadeIn">
                        @foreach ($children as $child)
                            <li><a class="dark-link {{ $child->id == $selectedCategory ? 'selected' : '' }}" href="{{ $prefix }}?category={{ $child->id }}&city={{ request('city') }}">{{ $child->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        <!--a href="#" class="list-group-item">Разное</a-->
        </div>
        @else
            <div class="alert alert-info" style="margin-bottom: 0">{{ __('layout.No categories available') }}</div>
        @endif
    </div>
</div>
<!-- / layouts/components/block-categories -->