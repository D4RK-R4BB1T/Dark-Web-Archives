<!-- layouts/components/modals/notifications -->
@component('layouts.components.component-modal', ['id' => 'notifications'])
    @slot('title')
        Уведомления
        @if ($unreadNotifications->count() > 0)
            <a href="{{ url('/notifications_read') }}?_token={{ csrf_token() }}" class="btn btn-orange btn-sm" style="margin-left: 10px">Отметить прочитанными</a>
        @endif
    @endslot
    @if ($unreadNotifications->count() == 0)
        <div class="alert alert-warning">Непрочитанных уведомлений нет</div>
    @else
        @foreach ($unreadNotifications as $notification)
            <div class="media">
                <div class="media-left" style="padding-right: 10px">
                    <img style="width: 32px" class="media-object" src="{{ asset('/assets/img/logo.svg') }}" alt="Solaris">
                </div>
                <div class="media-body">
                    <h5 class="media-heading" style="margin-bottom: 4px">
                        Solaris <span class="text-muted" style="padding-left: 10px"><i class="glyphicon glyphicon-calendar"></i> {{ $notification->created_at->format('d.m.Y') }}</span>
                    </h5>
                    {!! nl2br($notification->body) !!}
                </div>
            </div>
            @if (!$loop->last)
            <hr />
            @endif
        @endforeach
    @endif
    @slot('footer_btn', 'Закрыть')
@endcomponent
<!-- /layouts/components/modals/notifications -->