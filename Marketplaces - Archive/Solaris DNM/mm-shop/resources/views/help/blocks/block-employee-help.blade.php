<!-- help/blocks/block-employee-help -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Помощь</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url("/help/employee/markdown") }}" class="list-group-item {{ isset($pageName) && $pageName === 'markdown' ? 'active' : '' }}">Markdown</a>
            <a href="{{ url("/help/employee/qiwi_api") }}" class="list-group-item {{ isset($pageName) && $pageName === 'qiwi_api' ? 'active' : '' }}">Solaris QIWI Protocol</a>
            <a href="{{ url("/help/employee/exchange_api") }}" class="list-group-item {{ isset($pageName) && $pageName === 'exchange_api' ? 'active' : '' }}">Solaris Exchange API</a>
        </div>
    </div>
</div>
<!-- / help/blocks/block-employee-help -->