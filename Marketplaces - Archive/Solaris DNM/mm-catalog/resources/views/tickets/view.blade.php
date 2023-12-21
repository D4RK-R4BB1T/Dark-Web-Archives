@extends('layouts.master')

@section('title', __('feedback.Viewing ticket'))

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-18 col-md-18 col-lg-19 pull-right animated fadeIn">
            <div class="well block good-info">
                <div class="row">
                    <div class="col-xs-15">
                        <h3 class="title" style="margin-bottom: 1px; word-break: break-all">{{ $ticket->title }}</h3>
                    </div>

                    @if(\Auth::user()->isAdmin() || (!$ticket->closed && !\Auth::user()->isAdmin()))
                        <div class="col-xs-9 text-right">
                            <span class="dropdown">
                                <a class="dark-link" href="#" style="font-size: 15px"><i
                                            class="glyphicon glyphicon-cog"></i></a>
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                    <li role="presentation">
                                        <a href="#" class="no-padding">
                                            @if(!$ticket->closed)
                                                @component('layouts.components.component-modal-toggle', ['id' => 'ticket-close', 'class' => 'modal-logout-link', 'ticket' => $ticket])
                                                    <i class="glyphicon glyphicon-lock"></i> Закрыть обращение
                                                @endcomponent
                                            @elseif(\Auth::user()->isAdmin())
                                                @component('layouts.components.component-modal-toggle', ['id' => 'ticket-open', 'class' => 'modal-logout-link', 'ticket' => $ticket])
                                                    <i class="glyphicon glyphicon-pencil"></i> Открыть обращение
                                                @endcomponent
                                            @endif
                                        </a>
                                    </li>

                                    @if(\Auth::user()->isAdmin())
                                        <li role="presentation">
                                            <a href="#" class="no-padding">
                                                @component('layouts.components.component-modal-toggle', ['id' => 'ticket-delete', 'class' => 'modal-logout-link', 'ticket' => $ticket])
                                                    <i class="glyphicon glyphicon-trash"></i> Удалить обращение
                                                @endcomponent
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </span>
                        </div>
                    @endif
                </div>

                <hr class="small"/>

                @if(!$ticket->closed)
                    <form action="/ticket/{{ $ticket->id }}/comment" method="post" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <div class="form-group {{ $errors->has('message') ? 'has-error' : '' }}">
                            <textarea name="message" class="form-control" rows="10" placeholder="Напишите сообщение..."
                                      {{ autofocus_on_desktop() }}>{{ old('message') }}</textarea>
                            @if ($errors->has('message'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('message') }}</strong>
                                </span>
                            @endif
                        </div>
                        <div class="row">
                            <div class="col-xs-24 text-center">
                                <span class="help-block">
                                    Вы можете загрузить до 3-х картинок весом до 5 мб.
                                </span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-24">
                                <div class="form-group text-center {{ $errors->has('images.*') ? 'has-error' : '' }}">
                                    <div class="kd-upload">
                                        <span><i class="glyphicon glyphicon-upload"></i> <span class="upload-text">Картинки</span></span>
                                        <input type="file" name="images[]" class="upload" multiple>
                                    </div>
                                    <button type="submit" class="btn btn-orange">Отправить</button>
                                    @if ($errors->has('images.*'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('images.*') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="text-center">
                        <span class="text-muted">Обращение закрыто.</span>
                        <br/>
                        <div style="margin-top: 5px"></div>
                        {{--
                        @component('layouts.components.component-modal-toggle', ['id' => 'thread-delete', 'class' => 'btn btn-orange'])
                            Удалить диалог
                        @endcomponent
                        --}}
                    </div>
                @endif

                <hr class="small"/>

                <!-- вывод сообщений -->
                @foreach ($messages as $m)
                    <div class="media @if($m->user_id === $ticket->user_id)msg-user @else msg-admin @endif">
                        <img class="pull-left" src="{{ traverse($m, 'author->avatar()') ?: '' }}" width="32" alt="{{ traverse($m, 'author->getPublicName()') ?: '-' }}" />

                        <div class="media-body">
                            <div class="text-muted pull-right">
                                {{ $m->created_at->format('d.m.Y в H:i') }}
                                @if((isset($canDelete) && $canDelete))
                                    <a href="/admin/ticket/{{ $ticket->id }}/message/{{ $m->id }}/delete" class="hint hint--left hint--error" aria-label="Удалить сообщение">
                                        <i class="glyphicon glyphicon-trash text-red small"></i>
                                    </a>
                                @endif
                            </div>
                            <h4 style="margin-top: 0; margin-bottom: 3px;">
                                {!! traverse($m, 'author->getPublicDecoratedName()') ?: '-' !!}
                                @if (Auth::user()->isAdmin() && traverse($m, 'author->isAdmin()') === true)
                                    <span class="text-muted hint--right" aria-label="Настоящее имя пользователя, видно только администраторам">
                                        ({{ traverse($m, 'author->username') }})
                                    </span>
                                @endif
                            </h4>
                            <p>{!! \App\Packages\Utils\Formatters::formatMessage($m->text) !!}</p>
                        </div>

                        @if($m->files->count() > 0)
                            <div class="media-bottom">
                                <div class="row">
                                    <div class="col-xs-24">
                                        <span class="help-block">
                                            Пользователь прикрепил изображения:
                                        </span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xs-24">
                                        @foreach($m->files as $file)
                                            <a href="{{ url($file->url) }}" target="_blank">
                                                <img src="{{ url($file->thumbnail()) }}" alt="" class="img-thumbnail">
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <hr class="small">
                @endforeach

                @if ($messages->total() > $messages->perPage())
                    <hr class="small" />
                    <div class="text-center">
                        {{ $messages->appends(request()->input())->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xs-24 col-sm-6 col-md-6 col-lg-5 pull-left">
            @include('tickets.components.block-actions')
            @if (Auth::user()->isAdmin())
                @include('tickets.components.block-filters')
            @endif
        </div> <!-- /.col-lg-5 -->
    </div> <!-- /.row -->
@endsection

@section('modals')
    @include('tickets.components.modals.ticket-open')
    @include('tickets.components.modals.ticket-close')
    @include('tickets.components.modals.ticket-delete')
@endsection