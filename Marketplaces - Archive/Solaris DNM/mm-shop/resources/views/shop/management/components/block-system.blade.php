<!-- shop/management/components/block-system -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Управление магазином</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url("/shop/management/system/payments") }}" class="list-group-item {{ (isset($section) && $section === 'payments') ? 'active' : '' }}">Оплата</a>
            <a href="{{ url("/shop/management/system/integrations") }}" class="list-group-item {{ (isset($section) && $section === 'integrations') ? 'active' : '' }}">Интеграции</a>
        </div>
    </div>
</div>
<!-- / shop/management/components/block-system -->