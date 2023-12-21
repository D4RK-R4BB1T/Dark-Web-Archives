@extends('master.profile')

@section('profile-content')
    @include('includes.flash.error')
    @include('includes.flash.success')
    @include('includes.validation')


    <h1 class="my-3">Private Messages</h1>
    <hr>
    <div class="row justify-content-center">
        <div class="col-md-12">

            <a href="{{ route("profile.messages") }}">
                <h3 class="mb-3">Conversations</h3>
            </a>
            <hr>

            @if($usersConversations -> isNotEmpty())
                <div class="list-group mb-3">
                    <a href="{{ route("profile.messages") }}" class="list-group-item list-group-item-action list-group-item-indigo">
                        <i class="fas fa-user-plus"></i>
                        New Conversation
                    </a>
                    @foreach($usersConversations as $conversationItem)
                        <a href="{{ route('profile.messages', $conversationItem) }}"
                           class="list-group-item d-flex justify-content-between list-group-item-action">
                            {{ $conversationItem -> otherUser() -> username }}

                        <span class="d-flex">
                                @if($conversationItem -> unreadMessages() > 0)
                                    <span class="badge badge-warning d-flex align-items-center mx-1 badge-pill">Unread: {{ $conversationItem -> unreadMessages() }}</span>
                                @endif
                                <span class="badge badge-info d-flex mx-1 align-items-center badge-pill">Total messages: {{ $conversationItem -> messages() -> count() }}</span>
                                <span class="badge badge-light d-flex ml-1 align-items-center badge-pill">Updated {{ $conversationItem -> updated_ago }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
                {{ $usersConversations -> links('includes.paginate') }}
            @else
                <div class="alert alert-warning text-center">You don't have any conversations!</div>
            @endif
        </div>
    </div>



@stop