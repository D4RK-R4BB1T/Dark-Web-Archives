<!-- orders/components/block-orders -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Работа с заказами</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="?status=active" class="list-group-item {{ request('status') === 'active' ? 'active' : '' }}">Активные заказы</a>
            <a href="?" class="list-group-item {{ empty(request('status')) ? 'active' : '' }}">Все заказы</a>
        </div>
    </div>
</div>
<!-- / orders/components/block-orders -->