<!-- shop/management/messages/components/modals/thread-delete -->
<form class="form-horizontal" role="form" action="{{ url('/shop/management/messages/delete/'.$thread->id) }}" method="post">
    {{ csrf_field() }}
    <input type="hidden" name="redirect_to" value="{{ url('/shop/management/messages/delete/'.$thread->id) }}" />
    @component('layouts.components.component-modal', ['id' => 'thread-delete'])
        @slot('title', 'Удаление диалога')
        Вы действительно хотите удалить этот диалог?
        @slot('footer_btn_before')
            <button type="submit" class="btn btn-orange">Удалить диалог</button>
        @endslot
        @slot('footer_btn', 'Закрыть')
     @endcomponent
</form>
<!-- / shop/management/components/modals/thread-delete -->