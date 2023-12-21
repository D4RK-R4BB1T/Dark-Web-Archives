<!-- tickets/components/modals/ticket-delete -->
@component('layouts.components.component-modal', ['id' => 'ticket-delete'])
    @slot('title', 'Удаление тикета')
    Вы действительно хотите удалить этот тикет?
    @slot('footer_btn_before')
        <a href="/admin/ticket/{{ $ticket->id }}/delete">
            <button type="button" class="btn btn-orange">Удалить тикет</button>
        </a>
    @endslot
    @slot('footer_btn', 'Отмена')
@endcomponent
<!-- / tickets/components/modals/ticket-delete -->