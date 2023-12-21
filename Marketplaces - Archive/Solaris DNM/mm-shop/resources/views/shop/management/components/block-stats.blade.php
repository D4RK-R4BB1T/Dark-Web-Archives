<!-- shop/management/components/block-stats -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Разделы статистики</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url("/shop/management/stats/users") }}" class="list-group-item {{ (isset($section) && $section === 'users') ? 'active' : '' }}">Пользователи</a>
            <a href="{{ url("/shop/management/stats/orders") }}" class="list-group-item {{ (isset($section) && $section === 'orders') ? 'active' : '' }}">Покупки</a>
            <a href="{{ url("/shop/management/stats/accounting") }}" class="list-group-item {{ (isset($section) && $section === 'accounting') ? 'active' : '' }}">Учет товаров</a>
            <a href="{{ url("/shop/management/stats/filling") }}" class="list-group-item {{ (isset($section) && $section === 'filling') ? 'active' : '' }}">Заполненность магазина</a>
            <a href="{{ url("/shop/management/stats/employees") }}" class="list-group-item {{ (isset($section) && $section === 'employees') ? 'active' : '' }}">Курьеры</a>
        </div>
    </div>
</div>
<!-- / shop/management/components/block-stats -->