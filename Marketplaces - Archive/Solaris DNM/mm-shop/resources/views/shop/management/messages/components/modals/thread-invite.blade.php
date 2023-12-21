<!-- shop/management/messages/components/modals/thread-invite -->
<form class="form-horizontal" role="form" action="{{ url('/shop/management/messages/invite/'.$thread->id) }}" method="post">
    {{ csrf_field() }}
    @component('layouts.components.component-modal', ['id' => 'thread-invite'])
        @slot('title', 'Добавление сотрудника в диалог')
        Выберите сотрудника из списка:
        <div class="form-group">
            <select name="employee_id" class="form-control" aria-label="Сотрудник">
                @foreach($employees['rest'] as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->user->username }}</option>
                @endforeach
            </select>
        </div>
        @slot('footer_btn_before')
            <button type="submit" class="btn btn-orange">Добавить в диалог</button>
        @endslot
        @slot('footer_btn', 'Закрыть')
     @endcomponent
</form>
<!-- / shop/management/components/modals/thread-invite -->