<!-- shop/management/components/block-settings -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Настройки магазина</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url("/shop/management/settings/appearance") }}" class="list-group-item {{ (isset($section) && $section === 'appearance') ? 'active' : '' }}">Аватар и название магазина</a>
            <a href="{{ url("/shop/management/settings/blocks") }}" class="list-group-item {{ (isset($section) && $section === 'blocks') ? 'active' : '' }}">Блоки магазина</a>
            <a href="{{ url("/shop/management/settings/referral") }}" class="list-group-item {{ (isset($section) && $section === 'referral') ? 'active' : '' }}">Реферальная система</a>
        </div>
    </div>
</div>
<!-- / shop/management/components/block-settings -->