<!-- exchange/components/block-exchange -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Работа с обменами</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url("/exchange") }}" class="list-group-item {{ isset($section) && $section === 'exchange' ? 'active' : '' }}">Новый обмен</a>
            <a href="{{ url("/exchange/history") }}" class="list-group-item {{ isset($section) && $section === 'history' ? 'active' : '' }}">История обменов</a>
        </div>
    </div>
</div>
<!-- / exchange/components/block-exchange -->