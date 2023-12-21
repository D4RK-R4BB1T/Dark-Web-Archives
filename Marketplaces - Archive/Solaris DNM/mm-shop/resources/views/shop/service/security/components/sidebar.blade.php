<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Навигация</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url('/shop/service/security/shop') }}" class="list-group-item {{ $servicePage === 'shop' ? 'active' : ''}}">Магазин</a>
            <a href="{{ url('/shop/service/security/integrations') }}" class="list-group-item {{ $servicePage === 'integrations' ? 'active' : ''}}">Интеграции</a>
            <a href="{{ url('/shop/service/finances') }}" class="list-group-item {{ $servicePage === 'finances' ? 'active' : ''}}">Финансы</a>
            <a href="{{ url('/shop/service/security/plan') }}" class="list-group-item {{ $servicePage === 'plan' ? 'active' : ''}}">Тариф</a>
            <a href="{{ url('/shop/service/orders') }}" class="list-group-item {{ $servicePage === 'orders' ? 'active' : ''}}">Заказы</a>
            <a href="{{ url('/shop/service/security/users') }}" class="list-group-item {{ $servicePage === 'users' ? 'active' : '' }}">Пользователи</a>
        </div>
    </div>
</div>
