<!-- tickets/components/modals/ticket-close -->
@component('layouts.components.component-modal', ['id' => 'ticket-close'])
    @slot('title', 'Закрытие тикета')
    Вы действительно хотите закрыть этот тикет?
    @slot('footer_btn_before')
        <a href="/ticket/{{ $ticket->id }}/toggle">
            <button type="button" class="btn btn-orange">Закрыть тикет</button>
        </a>
    @endslot
    @slot('footer_btn', 'Отмена')
@endcomponent
<!-- / tickets/components/modals/ticket-close -->