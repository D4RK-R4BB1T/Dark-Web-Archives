<!-- shop/management/components/block-discounts -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Настройки скидок</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url("/shop/management/discounts/promo") }}" class="list-group-item {{ (isset($section) && $section === 'promo') ? 'active' : '' }}">Промо-коды</a>
            <a href="{{ url("/shop/management/discounts/groups") }}" class="list-group-item {{ (isset($section) && $section === 'groups') ? 'active' : '' }}">Скидочные группы</a>
        </div>
    </div>
</div>
<!-- / shop/management/components/block-discounts -->