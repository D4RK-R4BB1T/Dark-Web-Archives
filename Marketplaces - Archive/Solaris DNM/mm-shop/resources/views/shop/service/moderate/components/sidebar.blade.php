<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Навигация</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url('/shop/service/orders') }}" class="list-group-item {{ $servicePage === 'orders' ? 'active' : ''}}">Заказы</a>
            @if($role->id === \App\Role::SeniorModerator)
                <a href="{{ url('/shop/service/finances') }}" class="list-group-item {{ $servicePage === 'finances' ? 'active' : ''}}">Финансы</a>
            @endif
        </div>
    </div>
</div>
