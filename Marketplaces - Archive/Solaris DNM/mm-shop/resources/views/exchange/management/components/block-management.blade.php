<!-- exchange/management/components/block-management -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Администрирование</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url("/exchange/management/overview") }}" class="list-group-item {{ isset($section) && $section === 'overview' ? 'active' : '' }}">Панель информации</a>
            <a href="{{ url("/exchange/management/settings") }}" class="list-group-item {{ isset($section) && $section === 'settings' ? 'active' : '' }}">Настройки</a>
        </div>
    </div>
</div>
<!-- / exchange/management/components/block-management -->