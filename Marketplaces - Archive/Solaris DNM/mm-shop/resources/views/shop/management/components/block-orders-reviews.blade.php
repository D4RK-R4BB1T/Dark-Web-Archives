<!-- shop/management/components/block-orders-reviews -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Работа с отзывами</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url("/shop/management/orders/reviews") }}" class="list-group-item {{ (isset($section) && $section === 'reviews') ? 'active' : '' }}">Все отзывы</a>
        </div>
    </div>
</div>
<!-- / shop/management/components/block-orders-reviews -->