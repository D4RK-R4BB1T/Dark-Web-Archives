<!-- orders/components/block-orders -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">{{ __('orders.Order actions') }}</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="?status=active" class="list-group-item {{ request('status') === 'active' ? 'active' : '' }}">{{ __('orders.Active orders') }}</a>
            <a href="?" class="list-group-item {{ empty(request('status')) ? 'active' : '' }}">{{ __('orders.All orders') }}</a>
        </div>
    </div>
</div>
<!-- / orders/components/block-orders -->