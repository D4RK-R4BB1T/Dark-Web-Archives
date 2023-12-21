<!-- settings/components/block-settings-menu -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">{{ __('layout.Settings') }}</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="/settings/security" class="list-group-item {{ (isset($section) && $section === 'security') ? 'active' : '' }}">{{ __('layout.Password and security') }}</a>
        </div>
    </div>
</div>

<!-- / settings/components/block-settings-menu -->
