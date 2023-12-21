<!-- referral/components/block-referral-menu -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Реферальная система</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url("/referral/url") }}" class="list-group-item {{ (isset($section) && $section === 'url') ? 'active' : '' }}">Ссылки</a>
        </div>
    </div>
</div>
<!-- / referral/components/block-referral-menu -->
