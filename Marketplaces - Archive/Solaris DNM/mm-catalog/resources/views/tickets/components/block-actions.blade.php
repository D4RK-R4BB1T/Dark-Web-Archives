<!-- tickets/components/block-actions -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">{{ __('feedback.Actions') }}</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ \Auth::user()->isAdmin() ? '/admin' : '' }}/ticket" class="list-group-item">Все обращения</a>
            @if(!Auth::user()->isAdmin())
                <a href="/ticket/add" class="list-group-item @if(request()->is('ticket/add'))active @endif @cannot('create-ticket')cursor-not-allowed @endif">Создать обращение</a>
            @endif
        </div>
    </div>
</div>
<!-- / tickets/components/block-actions -->