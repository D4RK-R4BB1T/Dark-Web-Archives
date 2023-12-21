@extends('layouts.master')

@section('title', __('layout.Feedback'))

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-18 col-md-18 col-lg-19 pull-right animated fadeIn">
            <div class="well block good-info">
                <h3>{{ __('feedback.Ticket list') }}</h3>
                @if ($tickets->count() === 0)
                    <hr class="small"/>
                    <div class="alert alert-info" style="margin-bottom: 0">{{ __('feedback.No tickets found') }}</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-header table-hover" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>Заголовок</td>
                                <td></td>
                                <td>Категория</td>
                                <td>Автор</td>
                                <td>Дата</td>
                                <td>Статус</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($tickets as $t)
                                <tr style="cursor: pointer; {{ $t->closed ? 'background: #f1f1f1; font-style: italic' : '' }}" onclick="window.location='/ticket/{{ $t->id }}/view'" role="button">
                                    <td class="col-md-10">
                                        <a href="/ticket/{{ $t->id }}/view">{{ $t->title }}</a>
                                    </td>
                                    <td>
                                        @if($t->messages->count() == 1)
                                            <span class="hint--top" aria-label="Ожидает ответа">
                                                <i class="glyphicon glyphicon-exclamation-sign text-orange"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ __('feedback.Category ' . $t->category) }}</td>
                                    <td>
                                        @if($t->author)
                                            <a class="dark-link" href="?{{ http_build_query(request()->except('username')) }}&username={{ $t->author->username }}"><i class="glyphicon glyphicon-user"></i>&nbsp;<b>{{ $t->author->username }}</b></a>
                                        @else
                                            <i>-</i>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="hint--top" aria-label="Дата создания"><i class="glyphicon glyphicon-time"></i> {{ $t->created_at->format('d.m.Y H:i') }}</span><br />
                                        <span class="hint--top" aria-label="Дата последнего сообщения"><i class="glyphicon glyphicon-envelope"></i> {{ $t->last_message_at->format('d.m.Y H:i') }}</span>
                                    </td>
                                    <td><span class="hint--top" aria-label="Обращение {{ $t->closed ? 'закрыто' : 'открыто' }}"><i class="{{ $t->closed ? 'glyphicon glyphicon-lock text-red' : 'glyphicon glyphicon-comment text-green' }}"></i></span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($tickets->total() > $tickets->perPage())
                        <hr class="small"/>
                        <div class="text-center">
                            {{ $tickets->appends(request()->input())->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="col-xs-24 col-sm-6 col-md-6 col-lg-5 pull-left">
            @include('tickets.components.block-actions')
            @include('tickets.components.block-filters')
        </div> <!-- /.col-lg-5 -->
    </div> <!-- /.row -->
@endsection