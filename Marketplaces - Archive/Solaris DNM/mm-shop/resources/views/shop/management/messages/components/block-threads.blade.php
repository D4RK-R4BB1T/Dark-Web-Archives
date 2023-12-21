<!-- messages/components/block-threads -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">
        Диалоги
        <span class="dropdown pull-right">
            <a class="dark-link" href="#" style="font-size: 15px"><i class="glyphicon glyphicon-cog"></i></a>
            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                <li role="presentation"><a class="dark-link" role="menuitem" tabindex="-1" href="{{ url("/shop/management/messages/new") }}"><i class="glyphicon glyphicon-envelope"></i> Отправить сообщение</a></li>
                <li role="presentation"><a class="dark-link" role="menuitem" tabindex="-1" href="{{ url("/shop/management/messages/delete") }}"><i class="glyphicon glyphicon-trash"></i> Удалить выбранные диалоги</a></li>
            </ul>
        </span>
    </div>
    <div class="panel-body no-padding">
        @if ($threads->count() > 0)
            <div class="list-group hover-menu">
                @foreach($threads as $thread)
                    <?php $latestMessage = $thread->getLatestMessage(); ?>
                        @if(!isset($deleting) || !$deleting)
                            <a href="{{ url('/shop/management/messages/'.$thread->id.'?page='.request('page', 1)) }}" class="dark-link">
                        @endif
                        <div class="list-group-item {{ $thread->isUnread(-\Auth::user()->shop()->id) ? 'message-unread' : '' }}" style="display: table; table-layout: fixed; width: 100%; padding: 10px 6px">
                            <div class="col-xs-4 col-sm-6 col-md-5 col-lg-4"><img src="{{ url(traverse($latestMessage, 'author()->avatar()')) ?: url(noavatar()) }}" style="max-width: 48px; float: left;"></div>
                            <div class="col-xs-20 col-sm-18 col-md-19 col-lg-20">
                                <p style="margin-bottom: 0; word-wrap: break-word; text-overflow: ellipsis; overflow: hidden; white-space: nowrap">
                                    <strong style="float: left">{{ $thread->subject }}</strong>
                                    <small class="text-muted pull-right" style="padding-top: 2px">{{ $latestMessage->created_at->format('d.m в H:i') }}</small>
                                    <br />
                                    <small>Участники:</small> <small class="text-muted">{!! $thread->participantsString(-Auth::user()->shop()->id) ?: '-' !!}</small>
                                </p>
                                <p class="semi-small" style="word-wrap: break-word; text-overflow: ellipsis; overflow: hidden; white-space: nowrap">
                                    {{ mb_substr($latestMessage->body, 0, 95) }}
                                    @if(isset($deleting) && $deleting)
                                        <span class="pull-right">
                                            <input type="checkbox" name="threads[]" value="{{ $thread->id }}" />
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if(!isset($deleting) || !$deleting)
                            </a>
                        @endif
                    @if (!$loop->last)
                        <hr style="margin: 0" />
                    @endif
                @endforeach
                @if ($threads->total() > $threads->perPage())
                    <hr class="small" />
                    <div class="text-center" style="padding-left: 10px; padding-right: 10px">
                        {{ $threads->appends(request()->input())->links() }}
                    </div>
                @endif
            </div>
        @else
            <div class="alert alert-info" style="margin: 12px">Диалогов не найдено.</div>
        @endif
    </div>
</div>
<!-- / messages/components/block-threads -->