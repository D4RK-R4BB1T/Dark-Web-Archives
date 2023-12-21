<!-- shop/management/components/block-employees-actions -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Работа с сотрудниками</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ url("/shop/management/employees") }}" class="list-group-item {{ (isset($section) && $section === 'index') ? 'active' : '' }}">Лента действий</a>
            @if (isset($section) && $section === 'add')
                <a href="{{ url("/shop/management/employees/add") }}" class="list-group-item active">Добавить сотрудника</a>
            @else
                <a href="#" class="list-group-item no-padding">
                    @component('layouts.components.component-modal-toggle', ['id' => 'employees-add', 'class' => 'list-group-item'])
                        Добавить сотрудника
                    @endcomponent
                </a>
            @endif
        </div>
    </div>
</div>
<!-- / shop/management/components/block-employees-actions -->