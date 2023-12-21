@extends('master.profile')

@section('profile-content')
    @include('includes.flash.error')
    @include('includes.flash.success')
    @include('includes.validation')


    <h1 class="my-3">Private Messages</h1>
    <hr>
    <div class="row justify-content-center">
        <div class="col-md-4">

            <a href="{{ route("profile.messages") }}">
                <h3 class="mb-3">Conversations</h3>
            </a>
            <hr>

            @if(auth() -> user() -> conversations -> isNotEmpty())
                <div class="list-group">
                    <a href="{{ route("profile.messages") }}" class="list-group-item list-group-item-action @if(!$conversation) bg-info text-white @endif">
                        <i class="fas fa-user-plus"></i>
                        New Conversation
                    </a>
                    @foreach($usersConversations as $conversationItem)
                        <a href="{{ route('profile.messages', $conversationItem) }}"
                           class="list-group-item d-flex justify-content-between list-group-item-action @if($conversationItem  == $conversation) active @endif">

                            @if($conversationItem -> otherUser() -> exists)
                                {{ $conversationItem -> otherUser() -> username }}
                            @else
                                <em>{{ $conversationItem -> otherUser() -> username }}</em>
                            @endif
                            @if($conversationItem -> unreadMessages() > 0)
                                <span class="badge d-flex align-items-center badge-warning badge-pill">{{ $conversationItem -> unreadMessages() }}</span>
                            @endif
                        </a>
                    @endforeach
                    <a href="{{ route("profile.messages.conversations.list") }}" class="list-group-item list-group-item-action list-group-item-indigo @isroute('profile.messages.conversations.list') bg-info text-white @endisroute">
                        <i class="fas fa-list"></i>
                        All Conversations
                    </a>
                </div>

            @else
                <div class="alert alert-warning text-center">You don't have any conversations!</div>
            @endif
        </div>
        <div class="col-md-8">
            @if(!$conversation)
                <h3 class="mb-3">New conversation</h3>
                <hr>
                <form action="{{ route('profile.messages.conversation.new') }}" method="POST" class="my-2">
                    {{ csrf_field() }}
                    <input type="text" id="username" name="username" placeholder="Username of the receiver..."
                           class="form-control my-2 w-100" value="{{$new_conversation_other_party}}">
                    <textarea name="message" class="form-control my-2" rows="5"
                              placeholder="New message here"></textarea>
                    <button type="submit" class="btn btn-primary btn-block">Send message</button>
                </form>
            @else
                <h3 class="mb-3">Conversation with <em>{{ $conversation -> otherUser() -> username }}</em></h3>
                {{-- User is not a Stub --}}

                @if($conversation -> otherUser() -> exists)
                <form action="{{ route('profile.messages.message.new', $conversation) }}" method="POST">
                    <textarea class="form-control" rows="5" name="message"
                              placeholder="Place you message here, encrypted messages are welcome!"></textarea>
                    <button type="submit" class="btn btn-primary ml-auto mt-2 d-block">Send message</button>
                    {{ csrf_field() }}
                </form>
                @endif

                @if($conversation -> messages -> isEmpty())
                    <div class="alert alert-warning my-2">There is no messages in this conversation!</div>
                @else
                    @foreach($conversationMessages as $message)
                        <div class="card my-2">
                            <div class="card-body">
                                @if($message -> isEncrypted())
                                    <textarea readonly class="form-control"
                                              rows="5">{{ $message -> getContent() }}</textarea>
                                @else
                                    <p class="card-text">{{ $message -> getContent() }}</p>
                                @endif
                            </div>
                            <div class="card-footer text-right">
                                <small class="text-muted">by
                                    <strong>{{ $message -> getSender() -> username }}</strong>, {{ $message -> timeAgo() }}
                                </small>
                            </div>
                        </div>
                    @endforeach

                    {{ $conversationMessages -> links('includes.paginate') }}
                @endif
            @endif
        </div>

    </div>



@stop