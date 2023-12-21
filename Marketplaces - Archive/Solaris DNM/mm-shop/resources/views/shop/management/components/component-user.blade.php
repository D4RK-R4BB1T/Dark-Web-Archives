<?php
$title = isset($title) ? urlencode($title) : '';
?>
<!-- shop/management/components/component-user -->
<div class="well block" style="padding-bottom: 0">
    <div class="row">
        <div class="col-xs-12">
            <div class="row no-margin">
                <div class="icon-container"><img src="{{ url($user->avatar()) }}" class="icon img-circle" style="max-width: 60px" /></div>
                <div class="title-container" style="word-break: break-word;">
                    <div class="title">
                        <h4 style="margin: 0">{{ $user->getPublicName() }}
                        @if (Auth::user()->can('management-sections-messages'))
                            &nbsp;<a style="top: 2px" class="hint--top" aria-label="Отправить сообщение" href="{{ url('/shop/management/messages/new?user='.$user->id.'&title='.$title) }}"><i class="glyphicon glyphicon-envelope"></i></a>
                        @endif
                        </h4>
                    </div>
                    <div class="desc">
                        @if ($user->referrer)
                            Приглашен: <strong><i class="glyphicon glyphicon-user"></i> {{ $user->referrer->getPublicName() }}</strong> ({{ $user->referral_fee }}%)
                            <br />
                        @endif
                        <div class="text-muted">
                            {!! \App\Packages\Utils\Formatters::formatReview($user->note) !!}
                            <a href="#">
                                @component('layouts.components.component-modal-toggle', ['id' => 'usernote'])
                                    <small>(редактировать заметку)</small>
                                @endcomponent
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-10 col-md-offset-2">
            <span class="text-muted">Jabber:</span><span class="pull-right">{{ $user->contacts_jabber ?: '-' }}</span><br />
            <span class="text-muted">Telegram:</span><span class="pull-right">{{ $user->contacts_telegram ?: '-' }}</span><br />
            <span class="text-muted">Другое:</span><span class="pull-right">{{ $user->contacts_other ?: '-' }}</span><br />
        </div>
    </div>
    <div class="row user-info">
        <a href="{{ url('/shop/management/orders?user='.$user->id) }}">
            <div class="col-xs-5 col-xs-offset-4 text-center">
                <h4>{{ $user->buy_count }}</h4>
                сделано покупок
            </div>
        </a>
        <a href="{{ url('/shop/management/orders/reviews?user='.$user->id) }}">
            <div class="col-xs-6 col-xs-offset-4 text-center">
                <h4>{{ $user->goodsReviews()->count() }}</h4>
                отзывов о покупках
            </div>
        </a>
        {{--<div class="col-xs-7 text-center">--}}
            {{--<h4>0</h4>--}}
            {{--расширенных отзывов--}}
        {{--</div>--}}
        {{--<div class="col-xs-6 text-center">--}}
            {{--<h4>0</h4>--}}
            {{--отзывов о покупателе--}}
        {{--</div>--}}
    </div>
</div>
<!-- / shop/management/components/component-user -->

@section('modals')
    @include('shop.management.components.modals.usernote', ['user' => $user])
@endsection