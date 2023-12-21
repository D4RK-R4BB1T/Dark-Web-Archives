<?php
$prefix = isset($prefix) ? $prefix : '/catalog';

$hideOnMobileControllers = [
    'App\Http\Controllers\Shops\Management\GoodsController'
];

$hideOnMobileActions = [
    'App\Http\Controllers\Shops\ShopsController@good',
    'App\Http\Controllers\Shops\ShopsController@page'
];

$action = Route::getCurrentRoute()->getActionName();
list ($controller, $method) = explode('@', $action);

$hideOnMobile = in_array($controller, $hideOnMobileControllers) || in_array($action, $hideOnMobileActions);

$availableChildCategoriesIds = \App\Shop::getDefaultShop()->availableGoods()
    ->pluck('category_id')
    ->unique()
    ->toArray();

$availableParentCategories = \App\Category::getById($availableChildCategoriesIds)
    ->map(function($category) {
        return $category->parent();
    })
    ->unique();
?>
<!-- layouts/components/block-categories -->
<!-- prefix: {{ $prefix }} -->
<div class="{{ $hideOnMobile ? 'hidden-xs' : '' }} panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Категории</div>
    <div class="panel-body no-padding">
        @if (count($availableParentCategories) > 0)
        <div class="list-group hover-menu">
            @foreach ($availableParentCategories as $category)
                <div class="list-group-item">
                    <a class="dark-link" href="{{ url($prefix.'?category='.$category->id) }}">{{ $category->title }}</a>
                    <ul class="list-group-item-submenu hidden animated fadeIn">
                        @foreach ($category->children()->whereIn('id', $availableChildCategoriesIds) as $child)
                            <li><a class="dark-link" href="{{ url($prefix.'?category='.$child->id) }}">{{ $child->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        <!--a href="#" class="list-group-item">Разное</a-->
        </div>
        @else
            <div class="alert alert-info" style="margin-bottom: 0">Нет доступных категорий</div>
        @endif
    </div>
</div>
<!-- / layouts/components/block-categories -->