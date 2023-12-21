<!-- shop/management/components/block-qiwi -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Настройки оплаты QIWI</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url("/shop/management/qiwi") }}" class="list-group-item {{ (isset($section) && $section === 'index') ? 'active' : '' }}">Кошельки</a>
            <a href="{{ url("/shop/management/qiwi/operations") }}" class="list-group-item {{ (isset($section) && $section === 'operations') ? 'active' : '' }}">Операции</a>
            <a href="{{ url("/shop/management/qiwi/api") }}" class="list-group-item {{ (isset($section) && $section === 'api') ? 'active' : '' }}">API</a>
        </div>
    </div>
</div>
<!-- / shop/management/components/block-qiwi -->