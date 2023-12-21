<!-- shop/management/components/modals/employees-add -->
<form action="{{ url('/shop/management/employees/add') }}" method="post">
    {{ csrf_field() }}
    @component('layouts.components.component-modal', ['id' => 'employees-add'])
        @slot('title', 'Добавление сотрудника')
        <div class="row">
            <div class="col-xs-20 col-xs-offset-2">
                <div class="form-group" style="margin-bottom: 0">
                    <input id="title" type="text" class="form-control" name="username" placeholder="Введите логин пользователя" required>
                    <span class="help-block">
                        Пользователю будет отправлено приглашение присоединиться к магазину.
                    </span>
                </div>
            </div>
        </div>
        @slot('footer_btn_before')
            <button type="submit" class="btn btn-orange">Добавить сотрудника</button>
        @endslot
        @slot('footer_btn', 'Закрыть')
    @endcomponent
</form>
<!-- / shop/management/components/modals/employees-add -->