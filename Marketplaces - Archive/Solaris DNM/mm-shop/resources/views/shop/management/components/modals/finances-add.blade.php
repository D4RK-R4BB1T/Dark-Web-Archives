<!-- shop/management/components/modals/finances-add -->
<form action="{{ url('/shop/management/finances/add') }}" method="post">
    {{ csrf_field() }}
    @component('layouts.components.component-modal', ['id' => 'finances-add'])
    @slot('title', 'Добавление кошелька')
    <div class="row">
        <div class="col-xs-20 col-xs-offset-2">
            <div class="form-group" style="margin-bottom: 0">
                <input id="title" type="text" class="form-control" name="title" placeholder="Введите название нового кошелька" required>
                <span class="help-block">
                    Максимальное количество символов для названия - 20.
                </span>
            </div>
        </div>
    </div>
    @slot('footer_btn_before')
        <button type="submit" class="btn btn-orange">Создать кошелек</button>
    @endslot
    @slot('footer_btn', 'Закрыть')
    @endcomponent
</form>
<!-- / shop/management/components/modals/finances-add -->