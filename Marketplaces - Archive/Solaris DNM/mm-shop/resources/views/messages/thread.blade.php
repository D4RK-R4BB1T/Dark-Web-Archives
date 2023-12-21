{{--
This file is part of MM2-dev project.
Description: Messages list page
--}}
@extends('layouts.master')

@section('title', $thread->subject . ' :: Сообщения')

@section('content')
    <div class="row">
        <div class="col-sm-8 col-md-9 col-lg-8">
            @if (isset($deleting) && $deleting)
                @include('messages.components.block-deleting')
            @else
                @include('messages.components.block-threads', ['threads' => $threads, 'deleting' => false])
            @endif
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-16 col-md-15 col-lg-16 animated fadeIn">
            <div class="well block">
                <div class="row">
                    <div class="col-xs-15">
                        <h3 class="title" style="margin-bottom: 1px; word-break: break-all">{{ $thread->subject }}&nbsp;
                        @if ($thread->order)
                            @if ($thread->order->user_id == Auth::user()->id)
                                <small><a href="{{ url('/orders/'.$thread->order->id) }}" class="text-muted dark-link">(перейти к заказу)</a></small>
                            @elseif(($employee = Auth::user()->employee) && $thread->order->position
                                && $thread->order->position->employee_id == $employee->id) {{-- looks shitty, sorry for that --}}
                                <small><a href="{{ url('/shop/management/orders/'.$thread->order->id) }}" class="text-muted">(перейти к заказу)</a></small>
                            @endif
                        @endif
                        </h3>
                    </div>
                    <div class="col-xs-9 text-right">
                        Участники: {!! $thread->participantsString(Auth::id()) ?: '-' !!} &nbsp;
                        <span class="dropdown">
                            <a class="dark-link" href="#" style="font-size: 15px"><i class="glyphicon glyphicon-cog"></i></a>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li role="presentation">
                                    <a href="#" class="no-padding">
                                        @component('layouts.components.component-modal-toggle', ['id' => 'thread-delete', 'class' => 'modal-logout-link'])
                                            <i class="glyphicon glyphicon-trash"></i> Удалить диалог
                                        @endcomponent
                                    </a>
                                </li>
                            </ul>
                        </span>
                    </div>
                </div>
                <hr class="small" />
                @if ($thread->hasOtherParticipants(Auth::id()))
                    <form action="" method="post">
                        {{ csrf_field() }}
                        <div class="form-group {{ $errors->has('message') ? 'has-error' : '' }}">
                            <textarea name="message" class="form-control" rows="3" placeholder="Напишите сообщение..." required {{ autofocus_on_desktop() }}>{{ old('message') }}</textarea>
                            @if ($errors->has('message'))
                                <span class="help-block">
                                <strong>{{ $errors->first('message') }}</strong>
                            </span>
                            @endif
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-orange">Напишите сообщение</button>
                        </div>
                    </form>
                @else
                    <div class="text-center">
                        <span class="text-muted">Диалог отмечен как закрытый.</span>
                        <br />
                        <div style="margin-top: 5px"></div>
                        @component('layouts.components.component-modal-toggle', ['id' => 'thread-delete', 'class' => 'btn btn-orange'])
                            Удалить диалог
                        @endcomponent
                    </div>
                @endif
                <hr class="small" />
                @foreach ($messages as $message)
                    @if ($message->system)
                        <div class="well">
                            {!! \App\Packages\Utils\Formatters::formatMessage($message->body, false) !!}
                            <span class="pull-right text-muted">{{ $message->created_at->format('d.m.Y в H:i') }}</span>
                        </div>
                    @else
                        <div class="media">
                            <a class="pull-left" href="#">
                                <img src="{{ url(traverse($message, 'author()->avatar()')) ?: url(noavatar()) }}" width="32" alt="">
                            </a>
                            <div class="media-body">
                                <div class="text-muted pull-right">{{ $message->created_at->format('d.m.Y в H:i') }}</div>
                                <h4 style="margin-top: 0; margin-bottom: 3px;">
                                    @if($employee = traverse($message, 'employee'))
                                        {!! $employee->getPublicDecoratedName() !!}
                                    @else
                                        {!! traverse($message, 'author()->getPublicDecoratedName()') ?: '-' !!}
                                    @endif
                                </h4>
                                <p>{!! \App\Packages\Utils\Formatters::formatMessage($message->body) !!}</p>
                            </div>
                        </div>
                    @endif
                @endforeach
                @if ($messages->total() > $messages->perPage())
                    <hr class="small" />
                    <div class="text-center">
                        {{ $messages->appends(request()->input())->links() }}
                    </div>
                @endif
            </div>
        </div> <!-- /.col-sm-18 -->
    </div> <!-- /.row -->
@endsection

@section('modals')
    @include('messages.components.modals.thread-delete')
@endsection