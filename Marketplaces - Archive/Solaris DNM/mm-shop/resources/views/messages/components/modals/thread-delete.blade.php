<!-- messages/components/modals/thread-delete -->
<form class="form-horizontal" role="form" action="{{ url('/messages/delete/'.$thread->id) }}" method="post">
    {{ csrf_field() }}
    <input type="hidden" name="redirect_to" value="{{ url('/messages/delete/'.$thread->id) }}" />
    @component('layouts.components.component-modal', ['id' => 'thread-delete'])
        @slot('title', 'Удаление диалога')
        Вы действительно хотите удалить этот диалог?
        @slot('footer_btn_before')
            <button type="submit" class="btn btn-orange">Удалить диалог</button>
        @endslot
        @slot('footer_btn', 'Закрыть')
     @endcomponent
</form>
<!-- / messages/components/modals/thread-delete -->