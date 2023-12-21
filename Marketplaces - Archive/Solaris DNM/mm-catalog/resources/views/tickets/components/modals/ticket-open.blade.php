<!-- tickets/components/modals/ticket-open -->
@component('layouts.components.component-modal', ['id' => 'ticket-open'])
    @slot('title', 'Открытие тикета')
    Вы действительно хотите открыть этот тикет?
    @slot('footer_btn_before')
        <a href="/ticket/{{ $ticket->id }}/toggle">
            <button type="button" class="btn btn-orange">Открыть тикет</button>
        </a>
    @endslot
    @slot('footer_btn', 'Отмена')
@endcomponent
<!-- / tickets/components/modals/ticket-open -->