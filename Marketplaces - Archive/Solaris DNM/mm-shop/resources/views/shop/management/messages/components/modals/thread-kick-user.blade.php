<!-- shop/management/messages/components/modals/thread-kick-user -->
<form class="form-horizontal" role="form" action="{{ url('/shop/management/messages/kick/'.$thread->id) }}" method="post">
    {{ csrf_field() }}
    @component('layouts.components.component-modal', ['id' => 'thread-kick-user'])
        @slot('title', 'Удаление сотрудника из диалога')
        Выберите сотрудника из списка:
        <div class="form-group">
            <select name="employee_id" class="form-control" aria-label="Сотрудник">
                @foreach($employees['participants'] as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->user->username }}</option>
                @endforeach
            </select>
        </div>
        @slot('footer_btn_before')
            <button type="submit" class="btn btn-orange">Выгнать из диалога</button>
        @endslot
        @slot('footer_btn', 'Закрыть')
     @endcomponent
</form>
<!-- / shop/management/components/modals/thread-kick-user -->