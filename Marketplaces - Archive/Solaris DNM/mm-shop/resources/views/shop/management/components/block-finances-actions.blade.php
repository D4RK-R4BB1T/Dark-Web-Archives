<!-- shop/management/components/block-finances-actions -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Работа с финансами</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url("/shop/management/finances") }}" class="list-group-item {{ (isset($section) && $section === 'index') ? 'active' : '' }}">Кошельки</a>
            @if (isset($section) && $section === 'add')
                <a href="{{ url("/shop/management/finances/add") }}" class="list-group-item active">Добавить кошелек</a>
            @else
                <a href="#" class="list-group-item no-padding">
                    @component('layouts.components.component-modal-toggle', ['id' => 'finances-add', 'class' => 'list-group-item'])
                        Добавить кошелек
                    @endcomponent
                </a>
            @endif
        </div>
    </div>
</div>
<!-- / shop/management/components/block-finances-actions -->