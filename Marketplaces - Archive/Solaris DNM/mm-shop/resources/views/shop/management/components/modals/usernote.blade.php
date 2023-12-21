<!-- shop/management/components/modals/usernote -->
<form action="{{ url('/shop/management/users/note/'.$user->id) }}" method="post">
    {{ csrf_field() }}
    @component('layouts.components.component-modal', ['id' => 'usernote'])
        @slot('title', 'Редактирование заметки')
        <div class="row">
            <div class="col-xs-20 col-xs-offset-2">
                <div class="form-group" style="margin-bottom: 0">
                    <textarea rows="4" id="note" class="form-control" name="note" placeholder="Введите заметку...">{{ trim($user->note) }}</textarea>
                </div>
            </div>
        </div>
        @slot('footer_btn_before')
            <button type="submit" class="btn btn-orange">Отредактировать заметку</button>
        @endslot
        @slot('footer_btn', 'Закрыть')
    @endcomponent
</form>
<!-- / shop/management/components/modals/usernote -->