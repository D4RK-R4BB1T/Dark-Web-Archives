<!-- settings/components/block-settings-menu -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Настройки</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url("/settings/security") }}" class="list-group-item {{ (isset($section) && $section === 'security') ? 'active' : '' }}">Пароль и безопасность</a>
            <a href="{{ url("/settings/contacts") }}" class="list-group-item {{ (isset($section) && $section === 'contacts') ? 'active' : '' }}">Контакты</a>
        </div>
    </div>
</div>
<!-- / settings/components/block-settings-menu -->
